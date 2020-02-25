<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\View\Factory;

use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfigInterface;
use Mollie\Bundle\PaymentBundle\PaymentMethod\View\MolliePaymentView;

class MolliePaymentViewFactory implements MolliePaymentViewFactoryInterface
{
    /**
     * @inheritDoc
     */
    public function create(MolliePaymentConfigInterface $config)
    {
        return new MolliePaymentView($config);
    }
}