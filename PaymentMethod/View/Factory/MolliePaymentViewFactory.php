<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\View\Factory;

use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfigInterface;
use Mollie\Bundle\PaymentBundle\PaymentMethod\View\MolliePaymentView;

/**
 * Class MolliePaymentViewFactory
 *
 * @package Mollie\Bundle\PaymentBundle\PaymentMethod\View\Factory
 */
class MolliePaymentViewFactory implements MolliePaymentViewFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(MolliePaymentConfigInterface $config)
    {
        return new MolliePaymentView($config);
    }
}
