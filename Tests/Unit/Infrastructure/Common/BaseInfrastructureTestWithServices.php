<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common;

use DateTime;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Configuration\ConfigEntity;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Configuration\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\Interfaces\DefaultLoggerAdapter;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\Logger;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\LoggerConfiguration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Utility\Events\EventBus;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Utility\TimeProvider;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\Logger\TestDefaultLogger;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\Logger\TestShopLogger;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\ORM\MemoryStorage;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\ORM\TestRepositoryRegistry;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\TestShopConfiguration;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\Utility\TestTimeProvider;
use PHPUnit\Framework\TestCase;

/**
 * Class BaseTest.
 *
 * @package Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common
 */
abstract class BaseInfrastructureTestWithServices extends TestCase
{
    /**
     * @var TestShopConfiguration
     */
    public $shopConfig;
    /**
     * @var TestShopLogger
     */
    public $shopLogger;
    /**
     * @var TimeProvider
     */
    public $timeProvider;
    /**
     * @var TestDefaultLogger
     */
    public $defaultLogger;
    /**
     * @var array
     */
    public $eventHistory;

    /**
     * @throws \Exception
     */
    protected function setUp()
    {
        TestRepositoryRegistry::registerRepository(ConfigEntity::CLASS_NAME, MemoryRepository::getClassName());

        $me = $this;

        $this->timeProvider = new TestTimeProvider();
        $this->timeProvider->setCurrentLocalTime(new DateTime());
        $this->shopConfig = new TestShopConfiguration();
        $this->shopLogger = new TestShopLogger();
        $this->defaultLogger = new TestDefaultLogger();

        new TestServiceRegister(
            array(
                Configuration::CLASS_NAME => function () use ($me) {
                    return $me->shopConfig;
                },
                TimeProvider::CLASS_NAME => function () use ($me) {
                    return $me->timeProvider;
                },
                DefaultLoggerAdapter::CLASS_NAME => function () use ($me) {
                    return $me->defaultLogger;
                },
                ShopLoggerAdapter::CLASS_NAME => function () use ($me) {
                    return $me->shopLogger;
                },
                EventBus::CLASS_NAME => function () {
                    return EventBus::getInstance();
                },
            )
        );
    }

    protected function tearDown()
    {
        Logger::resetInstance();
        LoggerConfiguration::resetInstance();
        MemoryStorage::reset();
        TestRepositoryRegistry::cleanUp();

        parent::tearDown();
    }
}
