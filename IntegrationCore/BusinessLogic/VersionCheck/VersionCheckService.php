<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\VersionCheck;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\BaseService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\VersionCheck\Http\VersionCheckProxy;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;

/**
 * Class VersionCheckService
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\VersionCheck
 */
abstract class VersionCheckService extends BaseService
{

    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;

    /**
     * Fetches the latest plugin version, compares with the current and displays
     * message if version is outdated
     *
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function checkForNewVersion()
    {
        /** @var VersionCheckProxy $proxy */
        $proxy = ServiceRegister::getService(VersionCheckProxy::CLASS_NAME);

        $latestVersion = $proxy->getLatestPluginVersion($this->getConfigService()->getExtensionVersionCheckUrl());
        if (version_compare($latestVersion, $this->getConfigService()->getExtensionVersion(), 'gt')) {
            $this->flashMessage($latestVersion);
        }
    }

    /**
     * Display message in the shop
     *
     * @param string $latestVersion
     */
    abstract protected function flashMessage($latestVersion);

    /**
     * @return Configuration
     */
    protected function getConfigService()
    {
        /** @var Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);

        return $configService;
    }
}
