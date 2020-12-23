<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\View\Factory;

use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfigInterface;
use Mollie\Bundle\PaymentBundle\PaymentMethod\View\MolliePaymentView;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProvider;

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
    public function create(MolliePaymentConfigInterface $config, PaymentMethodProvider $provider)
    {
        return new MolliePaymentView($config, $provider);
    }
}
