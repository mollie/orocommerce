<?php

namespace Mollie\Bundle\PaymentBundle\EventListener;

use Mollie\Bundle\PaymentBundle\Entity\MollieSurchargeAwareInterface;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Provider\MolliePaymentConfigProviderInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;

/**
 * Class CheckoutEntityListener
 *
 * @package Mollie\Bundle\PaymentBundle\EventListener
 */
class CheckoutEntityListener
{
    /**
     * @var MolliePaymentConfigProviderInterface
     */
    private $paymentConfigProvider;

    /**
     * CheckoutEntityListener constructor.
     *
     * @param MolliePaymentConfigProviderInterface $paymentConfigProvider
     */
    public function __construct(
        MolliePaymentConfigProviderInterface $paymentConfigProvider
    ) {
        $this->paymentConfigProvider = $paymentConfigProvider;
    }

    public function onPrePersist(Checkout $checkout)
    {
        $this->setSurcharge($checkout);
    }

    public function onPreUpdate(Checkout $checkout)
    {
        $this->setSurcharge($checkout);
    }

    /**
     * @param Checkout $checkout
     */
    protected function setSurcharge(Checkout $checkout)
    {
        if (!$checkout instanceof MollieSurchargeAwareInterface) {
            return;
        }

        $paymentMethodConfig = $this->paymentConfigProvider->getPaymentConfig($checkout->getPaymentMethod());
        if ($paymentMethodConfig && $paymentMethodConfig->getSurchargeAmount() > 0) {
            $checkout->setMollieSurchargeAmount($paymentMethodConfig->getSurchargeAmount());
        } else {
            $checkout->setMollieSurchargeAmount(null);
        }
    }
}
