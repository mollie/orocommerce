<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Factory;

use Mollie\Bundle\PaymentBundle\Entity\PaymentMethodSettings;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfigInterface;

interface PaymentConfigFactoryInterface
{
    /**
     * @param PaymentMethodSettings $settings
     *
     * @return MolliePaymentConfigInterface
     */
    public function create(PaymentMethodSettings $settings);
}