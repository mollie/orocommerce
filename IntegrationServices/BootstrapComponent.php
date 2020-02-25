<?php
namespace Mollie\Bundle\PaymentBundle\IntegrationServices;

use Mollie\Bundle\PaymentBundle\Entity\Repository\MollieBaseEntityRepository;
use Mollie\Bundle\PaymentBundle\Entity\Repository\MollieContextAwareEntityRepository;
use Mollie\Bundle\PaymentBundle\Entity\Repository\MollieNotificationEntityRepository;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Interfaces\OrderLineTransitionService as OrderLineTransitionServiceInterface;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Interfaces\OrderTransitionService as OrderTransitionServiceInterface;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\Model\Notification;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Model\OrderReference;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Configuration\ConfigEntity;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\CurlHttpClient;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpClient;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\RepositoryRegistry;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BootstrapComponent extends \Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\BootstrapComponent
{
    /**
     * @var ContainerInterface
     */
    private static $container;
    /**
     * @var bool
     */
    private static $isInitialized = false;

    public static function boot(ContainerInterface $container)
    {
        self::$container = $container;

        if (!self::$isInitialized) {
            parent::init();
            self::$isInitialized = true;
        }
    }
    /**
     * Initializes services and utilities.
     */
    protected static function initServices()
    {
        parent::initServices();

        ServiceRegister::registerService(ContainerInterface::class, function() {
            return self::$container;
        });

        ServiceRegister::registerService(
            Configuration::CLASS_NAME,
            function () {
                return ConfigurationService::getInstance();
            }
        );

        ServiceRegister::registerService(
            ShopLoggerAdapter::CLASS_NAME,
            function () {
                return LoggerAdapter::getInstance();
            }
        );

        ServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () {
                return new CurlHttpClient();
            }
        );

        ServiceRegister::registerService(
            OrderTransitionServiceInterface::CLASS_NAME,
            function () {
                return self::$container->get(OrderTransitionService::class);
            }
        );

        ServiceRegister::registerService(
            OrderLineTransitionServiceInterface::CLASS_NAME,
            function () {
                return self::$container->get(OrderLineTransitionService::class);
            }
        );
    }

    /**
     * @inheritDoc
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryClassException
     */
    protected static function initRepositories()
    {
        parent::initRepositories();

        RepositoryRegistry::registerRepository(ConfigEntity::getClassName(), MollieBaseEntityRepository::getClassName());
        RepositoryRegistry::registerRepository(Notification::getClassName(), MollieNotificationEntityRepository::getClassName());
        RepositoryRegistry::registerRepository(
            PaymentMethodConfig::getClassName(),
            MollieContextAwareEntityRepository::getClassName()
        );
        RepositoryRegistry::registerRepository(
            OrderReference::getClassName(),
            MollieBaseEntityRepository::getClassName()
        );
    }
}