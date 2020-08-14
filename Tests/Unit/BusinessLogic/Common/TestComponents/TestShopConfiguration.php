<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Configuration;

class TestShopConfiguration extends Configuration
{
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;

    public function __construct()
    {
        parent::__construct();

        static::$instance = $this;
    }

    /**
     * Returns current system identifier.
     *
     * @return string Current system identifier.
     */
    public function getCurrentSystemId()
    {
        return 'test';
    }

    /**
     * Returns current system name.
     *
     * @return string Current system name.
     */
    public function getCurrentSystemName()
    {
        return  'test name';
    }

    /**
     * Retrieves integration (shop system) version.
     *
     * @return string Integration version.
     */
    public function getIntegrationVersion()
    {
        return 'test.v-1.0.0';
    }

    /**
     * Retrieves extension (plugin) version.
     *
     * @return string Extension version.
     */
    public function getExtensionVersion()
    {
        return 'mollie.test.v-1.0.0';
    }

    /**
     * Retrieves integration name.
     *
     * @return string Integration name.
     */
    public function getIntegrationName()
    {
        return 'test';
    }

    /**
     * Retrieves extension (plugin) name (for example MollieMagento2).
     *
     * @return string Extension name.
     */
    public function getExtensionName()
    {
        return 'mollieTest';
    }
}
