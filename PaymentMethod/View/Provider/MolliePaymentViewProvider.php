<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\View\Provider;

use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfigInterface;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Provider\MolliePaymentConfigProviderInterface;
use Mollie\Bundle\PaymentBundle\PaymentMethod\View\Factory\MolliePaymentViewFactoryInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProvider;
use Oro\Bundle\PaymentBundle\Method\View\AbstractPaymentMethodViewProvider;

/**
 * Class MolliePaymentViewProvider
 *
 * @package Mollie\Bundle\PaymentBundle\PaymentMethod\View\Provider
 */
class MolliePaymentViewProvider extends AbstractPaymentMethodViewProvider
{
    /** @var MOlliePaymentViewFactoryInterface */
    private $factory;

    /** @var MolliePaymentConfigProviderInterface */
    private $configProvider;
    /** @var PaymentMethodProvider */
    private $paymentMethodProvider;

    /**
     * MolliePaymentViewProvider constructor.
     *
     * @param MolliePaymentConfigProviderInterface $configProvider
     * @param MolliePaymentViewFactoryInterface $factory
     */
    public function __construct(
        MolliePaymentConfigProviderInterface $configProvider,
        MOlliePaymentViewFactoryInterface $factory,
        PaymentMethodProvider $paymentMethodProvider
    ) {
        $this->factory = $factory;
        $this->configProvider = $configProvider;
        $this->paymentMethodProvider = $paymentMethodProvider;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function buildViews()
    {
        $configs = $this->configProvider->getPaymentConfigs();
        foreach ($configs as $config) {
            $this->addPaymentView($config);
        }
    }

    /**
     * @param MolliePaymentConfigInterface $config
     */
    protected function addPaymentView(MolliePaymentConfigInterface $config)
    {

        $this->addView(
            $config->getPaymentMethodIdentifier(),
            $this->factory->create($config, $this->paymentMethodProvider)
        );
    }
}
