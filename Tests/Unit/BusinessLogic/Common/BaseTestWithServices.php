<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common;

use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Configuration\Configuration;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\TestShopConfiguration;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\BaseInfrastructureTestWithServices;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestServiceRegister;

abstract class BaseTestWithServices extends BaseInfrastructureTestWithServices
{
    /**
     * @var TestShopConfiguration
     */
    public $shopConfig;

    /**
     * @throws \Exception
     */
    protected function setUp()
    {
        parent::setUp();

        $instance = $this;

        $this->shopConfig = new TestShopConfiguration();

        TestServiceRegister::registerService(
            Configuration::CLASS_NAME,
            function () use ($instance) {
                return $instance->shopConfig;
            }
        );
    }
}
