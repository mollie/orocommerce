<?php

namespace Mollie\Bundle\PaymentBundle\Manager;

use Mollie\Bundle\PaymentBundle\Integration\MolliePaymentChannelType;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\UI\Controllers\AuthorizationController;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Manager\DeleteProviderInterface;

class MollieDeleteProvider implements DeleteProviderInterface
{
    /**
     * @var \Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\UI\Controllers\AuthorizationController
     */
    private $authorizationController;

    public function __construct(AuthorizationController $authorizationController)
    {
        $this->authorizationController = $authorizationController;
    }

    /**
     * @inheritDoc
     */
    public function supports($type)
    {
        return $type === MolliePaymentChannelType::TYPE;
    }

    /**
     * Process delete of integration related data
     *
     * @param Integration $integration
     */
    public function deleteRelatedData(Integration $integration)
    {
        /** @var Configuration $configuration */
        $configuration = ServiceRegister::getService(Configuration::CLASS_NAME);
        $configuration->doWithContext((string)$integration->getId(), function () use ($configuration) {
            $this->authorizationController->reset();
        });
    }
}