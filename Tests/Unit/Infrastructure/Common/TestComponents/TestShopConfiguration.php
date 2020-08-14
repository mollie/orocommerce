<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents;

use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Configuration\Configuration;

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
     * Retrieves integration name.
     *
     * @return string Integration name.
     */
    public function getIntegrationName()
    {
        return $this->getConfigValue('integrationName', 'test-system');
    }

    /**
     * Sets integration name.
     *
     * @param string $name Integration name.
     */
    public function setIntegrationName($name)
    {
        $this->saveConfigValue('integrationName', $name);
    }

    /**
     * Making the method public for testing purposes.
     *
     * {@inheritdoc}
     */
    public function getConfigEntity($name)
    {
        return parent::getConfigEntity($name);
    }
}
