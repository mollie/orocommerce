<?php

namespace Mollie\Bundle\PaymentBundle\Manager;

use Mollie\Bundle\PaymentBundle\Form\Entity\MollieRefund;
use Mollie\Bundle\PaymentBundle\Form\Entity\MollieRefundPayment;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Amount;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Payment;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Refunds\Refund;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Proxy;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\OrderReferenceService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\OrderService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Payments\PaymentService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Refunds\Exceptions\RefundNotAllowedException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Refunds\RefundService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Configuration\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\Logger;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;
use Oro\Bundle\LocaleBundle\Twig\LocaleExtension;
use Oro\Bundle\OrderBundle\Entity\Order;
use Symfony\Component\Form\Form;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class MollieRefundProvider
 *
 * @package Mollie\Bundle\PaymentBundle\Manager
 */
class MollieRefundProvider
{
    const PAYMENT_REFUND = '#payment_refund';

    /**
     * @var OroPaymentMethodUtility
     */
    private $paymentMethodUtility;
    /**
     * @var LocaleExtension
     */
    private $localeExtension;
    /**
     * @var OrderReferenceService
     */
    private $orderReferenceService;
    /**
     * @var OrderService
     */
    private $orderService;
    /**
     * @var TranslatorInterface
     */
    private $translationService;
    /**
     * @var Configuration
     */
    private $configService;
    /**
     * @var RefundService
     */
    private $refundService;
    /**
     * @var PaymentService
     */
    private $paymentService;

    /**
     * MollieRefundProvider constructor.
     *
     * @param Configuration $configService
     * @param RefundService $refundService
     * @param PaymentService $paymentService
     * @param OrderService $orderService
     * @param OrderReferenceService $orderReferenceService
     * @param OroPaymentMethodUtility $paymentMethodUtility
     * @param LocaleExtension $localeExtension
     * @param TranslatorInterface $translationService
     */
    public function __construct(
        Configuration $configService,
        RefundService $refundService,
        PaymentService $paymentService,
        OrderService $orderService,
        OrderReferenceService $orderReferenceService,
        OroPaymentMethodUtility $paymentMethodUtility,
        LocaleExtension $localeExtension,
        TranslatorInterface $translationService
    ) {
        $this->configService = $configService;
        $this->refundService = $refundService;
        $this->paymentService = $paymentService;
        $this->orderService = $orderService;
        $this->orderReferenceService = $orderReferenceService;
        $this->paymentMethodUtility = $paymentMethodUtility;
        $this->localeExtension = $localeExtension;
        $this->translationService = $translationService;
    }

    /**
     * @param Order $order
     *
     * @return MollieRefund
     */
    public function getMollieRefund($order)
    {
        $orderReference = $this->orderReferenceService->getByShopReference($order->getIdentifier());

        $voucherRefundProvider = new VoucherRefundFormProvider($orderReference, $this->localeExtension);
        if ($voucherRefundProvider->isVoucher()) {
            return $voucherRefundProvider->buildRefundForm();
        }

        $refund = new MollieRefund();

        $this->setPaymentRefund($order, $refund);

        $refund->setCurrency($order->getCurrency());
        $refund->setCurrencySymbol($this->localeExtension->getCurrencySymbolByCurrency($order->getCurrency()));

        return $refund;
    }

    /**
     * @param Form $form
     *
     * @return array
     */
    public function processRefundForm($form)
    {
        try {
            /** @var Order $order */
            $order = $form->getData()->data;
            $orderId = $order->getIdentifier();

            return $this->configService->doWithContext($this->paymentMethodUtility->getChannelId($order), function () use ($orderId,
                $form) {
                $mollieRefundForm = $this->extractRefundFromForm($form);
                if (!$mollieRefundForm) {
                    return [
                        'success' => false,
                        'message' => $this->translationService->trans('mollie.payment.refund.invalidForm'),
                    ];
                }

                $refundDto = $this->createRefundDTO($mollieRefundForm, PaymentMethodConfig::API_METHOD_PAYMENT);
                $orderReference = $this->orderReferenceService->getByShopReference($orderId);
                if ($orderReference && $orderReference->getApiMethod() === PaymentMethodConfig::API_METHOD_ORDERS) {
                    $this->refundService->refundWholeOrder($orderId, $refundDto);
                } else {
                    $this->refundService->refundPayment($orderId, $refundDto);
                }

                return [
                    'success' => true,
                    'message' => $this->translationService->trans('mollie.payment.refund.successMessage'),
                ];
            });
        } catch (\Exception $exception) {
            Logger::logError(
                'Failed to process refund action',
                'Integration',
                [
                    'ExceptionMessage' => $exception->getMessage(),
                    'ExceptionTrace' => $exception->getTraceAsString(),
                ]
            );

            return [
                'success' => false,
                'message' => $this->translationService->trans(
                    'mollie.payment.refund.errorMessage',
                    ['{api_message}' => $exception->getMessage()]
                ),
            ];
        }
    }

    /**
     * Checks if refund button should be displayed
     *
     * @param Order $order
     *
     * @return bool
     */
    public function displayRefundOption($order)
    {
        if ($order) {
            $isMollieSelected = $this->paymentMethodUtility->hasMolliePaymentConfig($order);
            $orderReference = $this->orderReferenceService->getByShopReference($order->getIdentifier());

            return $isMollieSelected
                && ($orderReference !== null)
                && !(new VoucherRefundFormProvider($orderReference, $this->localeExtension))
                    ->isVoucherWithoutReminderMethod()
                && !$this->paymentMethodUtility->isPaymentAuthorizedOnly($order);
        }

        return false;
    }

    /**
     * Extracts MollieRefund object from form
     *
     * @param Form $form
     *
     * @return MollieRefund|null
     */
    private function extractRefundFromForm($form)
    {
        $actionData = $form->getData();

        return $actionData ? $actionData->mollieRefund : null;
    }

    /**
     * @param MollieRefund $refundForm
     * @param $apiEndpointForUse
     *
     * @return Refund
     *
     * @throws RefundNotAllowedException
     */
    private function createRefundDTO(MollieRefund $refundForm, $apiEndpointForUse)
    {
        $refundDto = new Refund();
        $refundPayment = $refundForm->getRefundPayment();
        $amount = new Amount();
        $amount->setAmountValue($refundPayment->getAmount());
        $amount->setCurrency($refundForm->getCurrency());
        $refundDto->setAmount($amount);
        $refundDto->setDescription($refundPayment->getDescription());

        return $refundDto;
    }

    /**
     * @param Order $order
     * @param MollieRefund $refund
     */
    private function setPaymentRefund(Order $order, MollieRefund $refund)
    {
        $orderId = $order->getIdentifier();
        $orderReference = $this->orderReferenceService->getByShopReference($orderId);

        $isOrdersApi = $orderReference && $orderReference->getApiMethod() === PaymentMethodConfig::API_METHOD_ORDERS;

        $data = $this->configService->doWithContext($this->paymentMethodUtility->getChannelId($order),
            function () use ($orderId, $orderReference, $isOrdersApi) {
                if ($isOrdersApi) {
                    return ['entity' => $this->orderService->getOrder($orderId), 'captured' => null];
                }

                $payment = $this->paymentService->getPayment($orderId);
                $captured = null;
                if ($orderReference && $orderReference->getMollieReference()) {
                    /** @var Proxy $proxy */
                    $proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
                    $captures = $proxy->getCaptures($orderReference->getMollieReference());
                    if (!empty($captures)) {
                        $captured = 0.0;
                        foreach ($captures as $capture) {
                            if ($capture->getAmount()) {
                                $captured += (float)$capture->getAmount()->getValueAmount();
                            }
                        }
                    }
                }

                return ['entity' => $payment, 'captured' => $captured];
            }
        );

        $mollieEntity = $data['entity'];
        $refunded = $mollieEntity->getAmountRefunded()->getAmountValue();
        $totalAmount = $data['captured'] !== null
            ? $data['captured']
            : (float)$mollieEntity->getAmount()->getAmountValue();
        $remaining = max(0.0, $totalAmount - (float)$refunded);

        $refund->setTotalRefunded($refunded);
        $refund->setTotalValue($remaining);
        $paymentRefund = new MollieRefundPayment();
        $paymentRefund->setAmount($remaining);
        $refund->setRefundPayment($paymentRefund);
    }
}
