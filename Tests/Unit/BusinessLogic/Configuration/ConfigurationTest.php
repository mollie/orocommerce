<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Configuration;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\WebsiteProfile;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\BaseTestWithServices;

/**
 * Class ConfigurationTest.
 *
 * @package Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Configuration
 */
class ConfigurationTest extends BaseTestWithServices
{
    /**
     * Tests removing value from config service
     */
    public function testRemovingConfigValue()
    {
        $this->shopConfig->saveMinLogLevel(5);
        $this->assertEquals(5, $this->shopConfig->getMinLogLevel());
        $this->shopConfig->removeConfigValue('minLogLevel');
        $this->assertEquals(Configuration::MIN_LOG_LEVEL, $this->shopConfig->getMinLogLevel());
    }

    public function testRemovingContextSpecificConfigValue()
    {
        $this->shopConfig->setContext('test_context');
        $this->shopConfig->setAuthorizationToken('test_token');
        $this->assertEquals('test_token', $this->shopConfig->getAuthorizationToken());
        $this->shopConfig->removeConfigValue('authToken');
        $this->assertNull($this->shopConfig->getAuthorizationToken());
    }

    public function testSettingConfigValue()
    {
        $this->shopConfig->setContext('test_context');
        $this->shopConfig->setWebsiteProfile(WebsiteProfile::fromArray(array(
            'resource' => 'profile',
            'id' => 'test_dfklasjio11231',
            'name' => 'Test profile',
        )));
        $this->assertNotNull($this->shopConfig->getWebsiteProfile());
        $this->assertEquals('test_dfklasjio11231', $this->shopConfig->getWebsiteProfile()->getId());
        $this->assertEquals('Test profile', $this->shopConfig->getWebsiteProfile()->getName());
    }
}
