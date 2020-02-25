<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationServices;

use Oro\Bundle\PlatformBundle\Provider\PackageProvider;

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
        return $this->packageProvider->getOroPackages()[$this->getIntegrationName()]->getFullPrettyVersion();
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