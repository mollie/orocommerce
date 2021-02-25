<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationServices;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Configuration;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

/**
 * Class ConfigurationService
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationServices
 */
class ConfigurationService extends Configuration
{
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;

    /**
     * @var PlatformVersionReader
     */
    private $platformVersionReader;
    /**
     * @var WebsiteManager
     */
    private $websiteManager;
    /**
     * @var string
     */
    private $version;

    /**
     * @param PlatformVersionReader $platformVersionReader
     * @param WebsiteManager $websiteManager
     * @param $version
     *
     * @return ConfigurationService
     */
    public static function create(
        PlatformVersionReader $platformVersionReader,
        WebsiteManager $websiteManager,
        $version
    ) {
        $instance = static::getInstance();
        $instance->platformVersionReader = $platformVersionReader;
        $instance->websiteManager = $websiteManager;
        $instance->version = $version;

        return $instance;
    }

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
        return $this->platformVersionReader->getIntegrationVersion();
    }

    /**
     * Retrieves integration name.
     *
     * @return string Integration name.
     */
    public function getIntegrationName()
    {
        return $this->platformVersionReader->getIntegrationName();
    }

    /**
     * Retrieves extension (plugin) version.
     *
     * @return string Extension version.
     */
    public function getExtensionVersion()
    {
        $installedPackageVersion = $this->platformVersionReader->getMolliePackageVersion();

        return $installedPackageVersion ?: $this->version;
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
     * @inheritDoc
     * @return string
     */
    public function getExtensionVersionCheckUrl()
    {
        return '';
    }

    /**
     * @inheritDoc
     * @return string
     */
    public function getExtensionDownloadUrl()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function isSystemSpecific($name)
    {
        return false;
    }

    /**
     * @return \Oro\Bundle\WebsiteBundle\Entity\Website
     */
    private function getWebsite()
    {
        return $this->websiteManager->getCurrentWebsite() ?: $this->websiteManager->getDefaultWebsite();
    }
}
