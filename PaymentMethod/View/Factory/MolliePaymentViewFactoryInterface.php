<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\View\Factory;

use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfigInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;

interface MolliePaymentViewFactoryInterface
{
    /**
     * @param MolliePaymentConfigInterface $config
     * @return PaymentMethodViewInterface
     */
    public function create(MolliePaymentConfigInterface $config);
}