<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\Factory;

use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfigInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

interface MolliePaymentPaymentMethodFactoryInterface
{
    /**
     * @param MolliePaymentConfigInterface $config
     * @return PaymentMethodInterface
     */
    public function create(MolliePaymentConfigInterface $config);
}