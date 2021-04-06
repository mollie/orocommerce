<?php

namespace Mollie\Bundle\PaymentBundle\Manager;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\OrderReferenceService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfigInterface;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Provider\MolliePaymentContextAwareConfigProviderInterface;
use Oro\Bundle\LocaleBundle\Twig\LocaleExtension;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Context\PaymentContext;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

/**
 * Class OroPaymentMethodUtility
 *
 * @package Mollie\Bundle\PaymentBundle\Manager
 */
class OroPaymentMethodUtility
{
    /**
     * @var PaymentTransactionProvider
     */
    private $paymentTransactionProvider;
    /**
     * @var MolliePaymentContextAwareConfigProviderInterface
     */
    private $molliePaymentConfigProvider;
    /**
     * @var LocaleExtension
     */
    private $localeExtension;
    /**
     * @var OrderReferenceService
     */
    private $orderReferenceService;

    /**
     * OroPaymentMethodUtility constructor.
     *
     * @param PaymentTransactionProvider $paymentTransactionProvider
     * @param MolliePaymentContextAwareConfigProviderInterface $molliePaymentConfigProvider
     * @param OrderReferenceService $orderReferenceService
     * @param LocaleExtension $localeExtension
     */
    public function __construct(
        PaymentTransactionProvider $paymentTransactionProvider,
        MolliePaymentContextAwareConfigProviderInterface $molliePaymentConfigProvider,
        OrderReferenceService $orderReferenceService,
        LocaleExtension $localeExtension
    ) {
        $this->paymentTransactionProvider = $paymentTransactionProvider;
        $this->molliePaymentConfigProvider = $molliePaymentConfigProvider;
        $this->orderReferenceService = $orderReferenceService;
        $this->localeExtension = $localeExtension;
    }

    /**
     * Returns specific label for voucher method order
     *
     * @param Order $order
     *
     * @return array
     */
    public function getPaymentLabels($order)
    {
        $data = [
            'voucherLabel' => null,
            'methods' => $this->paymentTransactionProvider->getPaymentMethods($order),
        ];

        $orderReference = $this->orderReferenceService->getByShopReference($order->getIdentifier());
        if ($orderReference) {
            $voucherFormProvider = new VoucherRefundFormProvider($orderReference, $this->localeExtension);
            if ($voucherFormProvider->isVoucher()) {
                $data['voucherLabel'] = $voucherFormProvider->formatLabelWithReminder(
                    $this->molliePaymentConfigProvider->getPaymentConfigs(),
                    $this->getChannelId($order)
                );
            }
        }

        return $data;
    }

    /**
     * Returns payment method key
     *
     * @param Order $order
     *
     * @return string
     */
    public function getPaymentKey(Order $order)
    {
        $transaction = $this->paymentTransactionProvider->getPaymentTransaction($order);

        return $transaction ? $transaction->getPaymentMethod() : '';
    }

    /**
     * @param Order $order
     *
     * @return string|null
     */
    public function getChannelId(Order $order)
    {
        $paymentConfig = $this->getNoContextMolliePaymentConfigProvider()->getPaymentConfig($this->getPaymentKey($order));
        if (!$paymentConfig) {
            return null;
        }

        return $paymentConfig->getChannelId();
    }

    /**
     * @param Order $order
     *
     * @return bool
     */
    public function hasMolliePaymentConfig(Order $order, $ignoreAdminPaymentLink = false)
    {
        $paymentKey = $this->getPaymentKey($order);
        if (empty($paymentKey)) {
            return false;
        }

        if ($ignoreAdminPaymentLink && $paymentKey === MolliePaymentConfigInterface::ADMIN_PAYMENT_LINK_ID) {
            return false;
        }

        return $this->getNoContextMolliePaymentConfigProvider()->hasPaymentConfig($paymentKey);
    }

    /**
     * @param Order $order
     *
     * @return bool
     */
    public function isOrderValidForOrdersApi(Order $order)
    {
        $billingAddress = $order->getBillingAddress();
        return $billingAddress &&
            !empty($billingAddress->getCountryIso2()) &&
            !empty($billingAddress->getFirstName()) &&
            !empty($billingAddress->getLastName()) &&
            !empty($order->getEmail());
    }

    /**
     * @param Order $order
     * @return MolliePaymentConfigInterface[]
     */
    public function getAvailablePaymentConfigs(Order $order)
    {
        $paymentConfigs = $this->getMolliePaymentConfigProviderFor($order)->getPaymentConfigs();
        if (empty($paymentConfigs)) {
            return  $paymentConfigs;
        }

        $firstChannelConfigs = [];
        /** @var MolliePaymentConfigInterface $firstConfig */
        $firstConfig = reset($paymentConfigs);
        foreach ($paymentConfigs as $paymentConfigId => $paymentConfig) {
            if ($firstConfig->getChannelId() === $paymentConfig->getChannelId()) {
                $firstChannelConfigs[$paymentConfig->getPaymentMethodIdentifier()] = $paymentConfig;
            }
        }

        return $firstChannelConfigs;
    }

    /**
     * @return MolliePaymentContextAwareConfigProviderInterface
     */
    private function getNoContextMolliePaymentConfigProvider(): MolliePaymentContextAwareConfigProviderInterface
    {
        $this->molliePaymentConfigProvider->setPaymentContext(null);
        $this->molliePaymentConfigProvider->setApiMethod(PaymentMethodConfig::API_METHOD_ORDERS);
        return $this->molliePaymentConfigProvider;
    }

    /**
     * @return MolliePaymentContextAwareConfigProviderInterface
     */
    private function getMolliePaymentConfigProviderFor(Order $order): MolliePaymentContextAwareConfigProviderInterface
    {
        $paymentContext = new PaymentContext([
            PaymentContext::FIELD_BILLING_ADDRESS => $order->getBillingAddress(),
            PaymentContext::FIELD_TOTAL => $order->getTotal(),
            PaymentContext::FIELD_CURRENCY => $order->getCurrency(),
        ]);

        $apiMethod = PaymentMethodConfig::API_METHOD_PAYMENT;
        if ($this->isOrderValidForOrdersApi($order)) {
            $apiMethod = PaymentMethodConfig::API_METHOD_ORDERS;
        }

        $this->molliePaymentConfigProvider->setPaymentContext($paymentContext);
        $this->molliePaymentConfigProvider->setApiMethod($apiMethod);
        return $this->molliePaymentConfigProvider;
    }
}
