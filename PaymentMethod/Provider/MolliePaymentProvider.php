<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\Provider;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfigInterface;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Provider\MolliePaymentConfigProviderInterface;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Factory\MolliePaymentPaymentMethodFactoryInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\AbstractPaymentMethodProvider;

/**
 * Class MolliePaymentProvider
 *
 * @package Mollie\Bundle\PaymentBundle\PaymentMethod\Provider
 */
class MolliePaymentProvider extends AbstractPaymentMethodProvider
{
    /**
     * @var MolliePaymentPaymentMethodFactoryInterface
     */
    protected $factory;

    /**
     * @var MolliePaymentConfigProviderInterface
     */
    private $configProvider;

    /**
     * @param MolliePaymentConfigProviderInterface $configProvider
     * @param MolliePaymentPaymentMethodFactoryInterface $factory
     */
    public function __construct(
        MolliePaymentConfigProviderInterface $configProvider,
        MolliePaymentPaymentMethodFactoryInterface $factory
    ) {
        parent::__construct();

        $this->configProvider = $configProvider;
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    protected function collectMethods()
    {
        $configs = $this->configProvider->getPaymentConfigs();
        foreach ($configs as $config) {
            if (in_array($config->getMollieId(), PaymentMethodConfig::$paymentOnlyApiMethods)) {
                $config->set('api_method', PaymentMethodConfig::API_METHOD_PAYMENT);
            }
            $this->addPaymentMethod($config);
        }
    }

    /**
     * @param MolliePaymentConfigInterface $config
     */
    protected function addPaymentMethod(MolliePaymentConfigInterface $config)
    {
        $this->addMethod(
            $config->getPaymentMethodIdentifier(),
            $this->factory->create($config)
        );
    }
}
