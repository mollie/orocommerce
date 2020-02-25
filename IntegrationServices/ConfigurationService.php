<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationServices;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConfigurationService
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationServices
 */
class ConfigurationService extends Configuration
{
    /**
     * Returns current system identifier.
     *
     * @return string Current system identifier.
     */
    public function getCurrentSystemId()
    {
        return (string)($this->getWebsite()->getId());
    }

    /**
     * Returns current system name.
     *
     * @return string
     */
    public function getCurrentSystemName()
    {
        return $this->getWebsite()->getName();
    }

    /**
     * Retrieves integration (shop system) version.
     *
     * @return string Integration version.
     */
    public function getIntegrationVersion()
    {
        /** @var ContainerInterface $container */
        $container = ServiceRegister::getService(ContainerInterface::class);
        $platformVersionReader = $container->get('mollie_payment.platform_version_reader');

        return $platformVersionReader->getIntegrationVersion();
    }

    /**
     * Retrieves integration name.
     *
     * @return string Integration name.
     */
    public function getIntegrationName()
    {
        /** @var ContainerInterface $container */
        $container = ServiceRegister::getService(ContainerInterface::class);
        $platformVersionReader = $container->get('mollie_payment.platform_version_reader');

        return $platformVersionReader->getIntegrationName();
    }

    /**
     * Retrieves extension (plugin) version.
     *
     * @return string Extension version.
     */
    public function getExtensionVersion()
    {
        return '0.0.1';
    }

    /**
     * Retrieves extension (plugin) name (for example MollieMagento2).
     *
     * @return string Extension name.
     */
    public function getExtensionName()
    {
        return 'MollieOroCommerce';
    }

    /**
     * @return \Oro\Bundle\WebsiteBundle\Entity\Website
     */
    private function getWebsite()
    {
        /** @var ContainerInterface $container */
        $container = ServiceRegister::getService(ContainerInterface::class);
        /** @var WebsiteManager $websiteManager */
        $websiteManager = $container->get('oro_website.manager');

        return $websiteManager->getCurrentWebsite() ?: $websiteManager->getDefaultWebsite();
    }
}
