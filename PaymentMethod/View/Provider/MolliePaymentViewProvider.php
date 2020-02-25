<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\View\Provider;

use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfigInterface;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Provider\MolliePaymentConfigProviderInterface;
use Mollie\Bundle\PaymentBundle\PaymentMethod\View\Factory\MolliePaymentViewFactoryInterface;
use Oro\Bundle\PaymentBundle\Method\View\AbstractPaymentMethodViewProvider;

class MolliePaymentViewProvider extends AbstractPaymentMethodViewProvider
{
    /** @var MOlliePaymentViewFactoryInterface */
    private $factory;

    /** @var MolliePaymentConfigProviderInterface */
    private $configProvider;

    /**
     * @param MolliePaymentConfigProviderInterface $configProvider
     * @param MOlliePaymentViewFactoryInterface $factory
     */
    public function __construct(
        MolliePaymentConfigProviderInterface $configProvider,
        MOlliePaymentViewFactoryInterface $factory
    ) {
        $this->factory = $factory;
        $this->configProvider = $configProvider;

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
            $this->factory->create($config)
        );
    }
}