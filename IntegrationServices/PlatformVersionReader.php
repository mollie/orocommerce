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
        return $this->packageProvider->getOroPackages()[$this->getIntegrationName()]['pretty_version'];
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

        return $molliePackage ? $molliePackage->getFullPrettyVersion()['pretty_version'] : null;
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
