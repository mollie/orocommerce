<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Configuration;

use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\BaseInfrastructureTestWithServices;

/**
 * Class ConfigurationTest.
 *
 * @package Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Configuration
 */
class ConfigurationTest extends BaseInfrastructureTestWithServices
{
    /**
     * Tests storing and retrieving value from config service
     */
    public function testStoringValue()
    {
        $this->shopConfig->saveMinLogLevel(5);
        $this->assertEquals(5, $this->shopConfig->getMinLogLevel());
        $this->shopConfig->saveMinLogLevel(2);
        $this->assertEquals(2, $this->shopConfig->getMinLogLevel());

        $this->shopConfig->setDefaultLoggerEnabled(false);
        $this->assertFalse($this->shopConfig->isDefaultLoggerEnabled());
        $this->shopConfig->setDefaultLoggerEnabled(true);
        $this->assertTrue($this->shopConfig->isDefaultLoggerEnabled());

        $this->shopConfig->setDebugModeEnabled(false);
        $this->assertFalse($this->shopConfig->isDebugModeEnabled());
        $this->shopConfig->setDebugModeEnabled(true);
        $this->assertTrue($this->shopConfig->isDebugModeEnabled());
    }
}
