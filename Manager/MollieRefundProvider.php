<?php

namespace Mollie\Bundle\PaymentBundle\Manager;

use Mollie\Bundle\PaymentBundle\Form\Entity\MollieRefund;
use Mollie\Bundle\PaymentBundle\Form\Entity\MollieRefundLineItem;
use Mollie\Bundle\PaymentBundle\Form\Entity\MollieRefundPayment;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Amount;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\OrderLine;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Order as OrderDTO;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Payment;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Refunds\Refund;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\OrderReferenceService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\OrderService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Payments\PaymentService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Refunds\Exceptions\RefundNotAllowedException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Refunds\RefundService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Configuration\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\Logger;
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
    const ORDER_LINE_REFUND = '#order_line_refund';
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
        $isOrdersApiUsed = $orderReference ? $orderReference->getApiMethod() ===
            PaymentMethodConfig::API_METHOD_ORDERS : false;

        $voucherRefundProvider = new VoucherRefundFormProvider($orderReference, $this->localeExtension);
        if ($voucherRefundProvider->isVoucher()) {
            return $voucherRefundProvider->buildRefundForm();
        }

        $refund = new MollieRefund();
        $refund->setIsOrderApiUsed($isOrdersApiUsed);

        if ($isOrdersApiUsed) {
            $this->setRefundableItems($order, $refund);
        } else {
            $this->setPaymentRefund($order, $refund);
        }

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

                $isOrderApiUsed = $this->orderReferenceService->getApiMethod($orderId) === PaymentMethodConfig::API_METHOD_ORDERS;

                if ($mollieRefundForm->getSelectedTab() === self::PAYMENT_REFUND) {
                    $refundDto = $this->createRefundDTO($mollieRefundForm, PaymentMethodConfig::API_METHOD_PAYMENT);
                    $refundMethod = $isOrderApiUsed ? 'refundWholeOrder' : 'refundPayment';
                    $this->refundService->{$refundMethod}($orderId, $refundDto);
                } else {
                    $refundDto = $this->createRefundDTO($mollieRefundForm, PaymentMethodConfig::API_METHOD_ORDERS);
                    $this->refundService->refundOrderLines($orderId, $refundDto);
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
                    ->isVoucherWithoutReminderMethod();
        }

        return false;
    }

    /**
     * Set refund lines on refund form
     *
     * @param Order $order
     * @param MollieRefund $refund
     */
    private function setRefundableItems(Order $order, MollieRefund $refund)
    {
        /** @var OrderDTO $mollieOrder */
        $mollieOrder = $this->configService->doWithContext($this->paymentMethodUtility->getChannelId($order), function () use ($order) {
            return $this->orderService->getOrder($order->getIdentifier());
        });

        $refundItems = [];
        $refund->setIsOrderRefundable(false);

        foreach ($mollieOrder->getLines() as $mollieItem) {
            if ($mollieItem->getRefundableQuantity() > 0) {
                $refund->setIsOrderRefundable(true);
            }

            if ($mollieItem->getType() === 'discount') {
                continue;
            }
            $refundItems[] = $this->buildRefundItem($mollieItem);
        }

        $refund->setRefundItems($refundItems);
        $refunded = $mollieOrder->getAmountRefunded()->getAmountValue();
        $refund->setTotalRefunded($refunded);
        $refund->setTotalValue($mollieOrder->getAmount()->getAmountValue() - $refunded);
        $paymentRefund = new MollieRefundPayment();
        $paymentRefund->setAmount($mollieOrder->getAmount()->getAmountValue() - $refunded);
        $refund->setRefundPayment($paymentRefund);
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
     * @param OrderLine $mollieOrderLine
     *
     * @return MollieRefundLineItem
     */
    private function buildRefundItem(OrderLine $mollieOrderLine)
    {
        $refundItem = new MollieRefundLineItem();
        $refundItem->setOrderedQuantity($mollieOrderLine->getQuantity());
        $price = number_format((float)$mollieOrderLine->getUnitPrice()->getAmountValue(), 2, '.', ' ');
        $refundItem->setPrice($price);
        $refundItem->setRefundedQuantity($mollieOrderLine->getQuantityRefunded());
        $refundItem->setProduct($mollieOrderLine->getName());
        if ($mollieOrderLine->getRefundableQuantity() > 0) {
            $refundItem->setQuantityToRefund($mollieOrderLine->getRefundableQuantity());
        }

        $refundItem->setIsRefundable($mollieOrderLine->getRefundableQuantity() > 0);
        $sku = '';
        if ($mollieOrderLine->getType() === 'physical') {
            $sku = $mollieOrderLine->getSku();
        } elseif ($mollieOrderLine->getType() === 'shipping_fee') {
            $sku = 'shipping';
        } elseif ($mollieOrderLine->getType() === 'surcharge') {
            $sku = $mollieOrderLine->getType();
        }

        $refundItem->setSku($sku);
        $refundItem->setMollieId($mollieOrderLine->getId());

        return $refundItem;
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

        if ($apiEndpointForUse === PaymentMethodConfig::API_METHOD_ORDERS) {
            $refundLines = [];
            /** @var MollieRefundLineItem $formItem */
            foreach ($refundForm->getRefundItems() as $formItem) {
                if ($formItem->getMollieId() !== null) {
                    $refundLines[] = $this->createOrderLineDto($formItem);
                }
            }

            $refundDto->setLines($refundLines);
        } else {
            $refundPayment = $refundForm->getRefundPayment();
            $amount = new Amount();
            $amount->setAmountValue($refundPayment->getAmount());
            $amount->setCurrency($refundForm->getCurrency());
            $refundDto->setAmount($amount);
            $refundDto->setDescription($refundPayment->getDescription());
        }

        return $refundDto;
    }

    /**
     * @param MollieRefundLineItem $formItem
     *
     * @return OrderLine
     * @throws RefundNotAllowedException
     */
    private function createOrderLineDto(MollieRefundLineItem $formItem)
    {
        $quantityToRefund = $formItem->getQuantityToRefund() !== null ? $formItem->getQuantityToRefund() : 0;
        if ($quantityToRefund > ($formItem->getRefundedQuantity() + $formItem->getOrderedQuantity())) {
            $allowedQuantity = $formItem->getOrderedQuantity() - $formItem->getRefundedQuantity();
            $message = "Operation not allowed: You are trying to refund {$quantityToRefund}, and allowed quantity is {$allowedQuantity}";
            throw new RefundNotAllowedException($message);
        }

        $line = new OrderLine();
        $line->setId($formItem->getMollieId());
        $line->setQuantity($quantityToRefund);

        return $line;
    }

    /**
     * @param Order $order
     * @param MollieRefund $refund
     */
    private function setPaymentRefund(Order $order, MollieRefund $refund)
    {
        /** @var Payment $molliePayment */
        $molliePayment = $this->configService->doWithContext($this->paymentMethodUtility->getChannelId($order), function () use ($order) {
            return $this->paymentService->getPayment($order->getIdentifier());
        });

        $refund->setIsOrderRefundable($molliePayment->getStatus() === 'paid');
        $refunded = $molliePayment->getAmountRefunded()->getAmountValue();
        $refund->setTotalRefunded($refunded);
        $refund->setTotalValue($molliePayment->getAmount()->getAmountValue() - $refunded);
        $paymentRefund = new MollieRefundPayment();
        $paymentRefund->setAmount($molliePayment->getAmount()->getAmountValue() - $refunded);
        $refund->setRefundPayment($paymentRefund);
    }
}
