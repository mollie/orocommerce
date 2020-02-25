<?php

namespace Mollie\Bundle\PaymentBundle\Manager;

use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Provider\MolliePaymentConfigProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
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
     * @var MolliePaymentConfigProviderInterface
     */
    private $molliePaymentConfigProvider;

    /**
     * OroPaymentMethodUtility constructor.
     *
     * @param PaymentTransactionProvider $paymentTransactionProvider
     * @param MolliePaymentConfigProviderInterface $molliePaymentConfigProvider
     */
    public function __construct(
        PaymentTransactionProvider $paymentTransactionProvider,
        MolliePaymentConfigProviderInterface $molliePaymentConfigProvider
    ) {
        $this->paymentTransactionProvider = $paymentTransactionProvider;
        $this->molliePaymentConfigProvider = $molliePaymentConfigProvider;
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
        $paymentConfig = $this->molliePaymentConfigProvider->getPaymentConfig($this->getPaymentKey($order));
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
    public function hasMolliePaymentConfig(Order $order)
    {
        return $this->molliePaymentConfigProvider->hasPaymentConfig($this->getPaymentKey($order));
    }
}
