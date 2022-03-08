<?php

namespace Mollie\Bundle\PaymentBundle\EventListener;

use Mollie\Bundle\PaymentBundle\Entity\MollieSurchargeAwareInterface;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Surcharge\SurchargeService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;
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

        if ($paymentMethodConfig) {
            $surcharge = $this->getSurchargeService()->calculateSurchargeAmount(
                $paymentMethodConfig->getSurchargeType(),
                $paymentMethodConfig->getSurchargeFixedAmount(),
                $paymentMethodConfig->getSurchargePercentage(),
                $paymentMethodConfig->getSurchargeLimit(),
                $this->calculateSubtotal($checkout)
            );

            $checkout->setMollieSurchargeAmount($surcharge);
        } else {
            $checkout->setMollieSurchargeAmount(null);
        }
    }

    /**
     * @return SurchargeService
     */
    protected function getSurchargeService()
    {
        /** @var SurchargeService $surchargeService */
        $surchargeService = ServiceRegister::getService(SurchargeService::CLASS_NAME);

        return $surchargeService;
    }

    /**
     * @param Checkout $checkout
     * @return float|int
     */
    protected function calculateSubtotal(Checkout $checkout)
    {
        $subtotal = 0;
        $checkoutSubtotals = $checkout->getSubtotals();
        foreach ($checkoutSubtotals as $checkoutSubtotal) {
            $subtotal += $checkoutSubtotal->getSubtotal()->getAmount();
        }

        return $subtotal;
    }
}
