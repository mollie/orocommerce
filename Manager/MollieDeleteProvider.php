<?php

namespace Mollie\Bundle\PaymentBundle\Manager;

use Mollie\Bundle\PaymentBundle\Integration\MolliePaymentChannelType;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\Interfaces\AuthorizationService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Configuration\Configuration;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Manager\DeleteProviderInterface;

/**
 * Class MollieDeleteProvider
 *
 * @package Mollie\Bundle\PaymentBundle\Manager
 */
class MollieDeleteProvider implements DeleteProviderInterface
{
    /**
     * @var AuthorizationService
     */
    private $authorizationService;
    /**
     * @var Configuration
     */
    private $configService;

    /**
     * MollieDeleteProvider constructor.
     *
     * @param AuthorizationService $authorizationService
     * @param Configuration $configService
     */
    public function __construct(
        AuthorizationService $authorizationService,
        Configuration $configService
    ) {
        $this->authorizationService = $authorizationService;
        $this->configService = $configService;
    }

    /**
     * {@inheritdoc}
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
        $this->configService->doWithContext((string)$integration->getId(), function () {
            $this->authorizationService->reset();
        });
    }
}
