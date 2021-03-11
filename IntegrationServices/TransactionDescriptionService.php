<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationServices;

use Mollie\Bundle\PaymentBundle\Form\Type\PaymentMethodSettingsType;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\PaymentTransactionDescriptionService;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Provider\MolliePaymentConfigProviderInterface;

/**
 * Class TransactionDescriptionService
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationServices
 */
class TransactionDescriptionService extends PaymentTransactionDescriptionService
{

    /**
     * @var MolliePaymentConfigProviderInterface
     */
    private $molliePaymentConfigProvider;

    /**
     * @param MolliePaymentConfigProviderInterface $molliePaymentConfigProvider
     *
     * @return TransactionDescriptionService
     */
    public static function create(MolliePaymentConfigProviderInterface  $molliePaymentConfigProvider)
    {
        $instance = parent::getInstance();
        $instance->molliePaymentConfigProvider = $molliePaymentConfigProvider;

        return $instance;
    }

    /**
     * @inheritDoc
     */
    protected function getDescription($methodIdentifier)
    {
        $configuration = $this->molliePaymentConfigProvider->getPaymentConfig($methodIdentifier);

        return $configuration ?
            $configuration->getTransactionDescription() :
            PaymentMethodSettingsType::DEFAULT_TRANSACTION_DESCRIPTION;
    }
}
