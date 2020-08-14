<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\Factory;

use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfigInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

/**
 * Interface MolliePaymentPaymentMethodFactoryInterface
 *
 * @package Mollie\Bundle\PaymentBundle\PaymentMethod\Factory
 */
interface MolliePaymentPaymentMethodFactoryInterface
{
    /**
     * @param MolliePaymentConfigInterface $config
     * @return PaymentMethodInterface
     */
    public function create(MolliePaymentConfigInterface $config);
}
