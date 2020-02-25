<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationServices;


use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\LogData;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Singleton;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoggerAdapter extends Singleton implements ShopLoggerAdapter
{
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;

    /**
     * @var \Mollie\Bundle\PaymentBundle\IntegrationServices\LoggerService
     */
    private $logger;

    /**
     * LoggerAdapter constructor.
     *
     * @throws \Exception
     */
    protected function __construct()
    {
        parent::__construct();

        /** @var ContainerInterface $container */
        $container = ServiceRegister::getService(ContainerInterface::class);
        $this->logger = $container->get('mollie_payment.logger');
    }

    /**
     * Log message in system
     *
     * @param LogData $data
     */
    public function logMessage(LogData $data)
    {
        $this->logger->logMessage($data);
    }
}