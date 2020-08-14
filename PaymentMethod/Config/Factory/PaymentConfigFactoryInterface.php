<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Factory;

use Mollie\Bundle\PaymentBundle\Entity\PaymentMethodSettings;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfigInterface;

/**
 * Interface PaymentConfigFactoryInterface
 *
 * @package Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Factory
 */
interface PaymentConfigFactoryInterface
{
    /**
     * @param PaymentMethodSettings $settings
     *
     * @return MolliePaymentConfigInterface
     */
    public function create(PaymentMethodSettings $settings);
}
