<?php

namespace Mollie\Bundle\PaymentBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Mollie\Bundle\PaymentBundle\Entity\MollieSurchargeAwareInterface;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Provider\MolliePaymentConfigProviderInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;

class CheckoutEntityListener
{
    /**
     * @var MolliePaymentConfigProviderInterface
     */
    private $paymentConfigProvider;

    public function __construct(
        MolliePaymentConfigProviderInterface $paymentConfigProvider
    ) {
        $this->paymentConfigProvider = $paymentConfigProvider;
    }

    public function onPrePersist(Checkout $checkout, LifecycleEventArgs $args)
    {
        if (!$checkout instanceof MollieSurchargeAwareInterface) {
            return;
        }

        $paymentMethodConfig = $this->paymentConfigProvider->getPaymentConfig($checkout->getPaymentMethod());
        if ($paymentMethodConfig && $paymentMethodConfig->isSurchargeSupported()) {
            $checkout->setMollieSurchargeAmount($paymentMethodConfig->getSurchargeAmount());
        } else {
            $checkout->setMollieSurchargeAmount(null);
        }
    }

    public function onPreUpdate(Checkout $checkout, LifecycleEventArgs $args)
    {
        if (!$checkout instanceof MollieSurchargeAwareInterface) {
            return;
        }

        $paymentMethodConfig = $this->paymentConfigProvider->getPaymentConfig($checkout->getPaymentMethod());
        if ($paymentMethodConfig && $paymentMethodConfig->isSurchargeSupported()) {
            $checkout->setMollieSurchargeAmount($paymentMethodConfig->getSurchargeAmount());
        } else {
            $checkout->setMollieSurchargeAmount(null);
        }

    }
}