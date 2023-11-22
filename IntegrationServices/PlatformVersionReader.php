<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationServices;

use Oro\Bundle\PlatformBundle\Provider\PackageProvider;

/**
 * Class PlatformVersionReader
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationServices
 */
class PlatformVersionReader
{
    /**
     * @var \Oro\Bundle\PlatformBundle\Provider\PackageProvider
     */
    private $packageProvider;

    /**
     * @param PackageProvider $packageProvider
     */
    public function __construct(PackageProvider $packageProvider)
    {
        $this->packageProvider = $packageProvider;
    }

    /**
     * Retrieves integration (shop system) version.
     *
     * @return string Integration version.
     */
    public function getIntegrationVersion()
    {
        $integrationDetails = $this->packageProvider->getOroPackages()[$this->getIntegrationName()];

        return is_array($integrationDetails) ? $integrationDetails['pretty_version'] : $integrationDetails->getFullPrettyVersion();
    }

    /**
     * Retrieves mollie version from installed composer package
     *
     * @return string|null
     */
    public function getMolliePackageVersion()
    {
        $molliePackage = null;

        $packageInterfaces = $this->packageProvider->getThirdPartyPackages();
        if (array_key_exists('mollie/orocommerce', $packageInterfaces)) {
            $molliePackage = $packageInterfaces['mollie/orocommerce'];
        }

        return $molliePackage ? (is_array($molliePackage) ? $molliePackage['pretty_version'] : $molliePackage->getFullPrettyVersion()) : null;
    }

    /**
     * Retrieves integration name.
     *
     * @return string Integration name.
     */
    public function getIntegrationName()
    {
        return 'oro/commerce';
    }
}
