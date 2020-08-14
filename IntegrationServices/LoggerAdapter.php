<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationServices;

use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\LogData;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Singleton;

/**
 * Class LoggerAdapter
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationServices
 */
class LoggerAdapter extends Singleton implements ShopLoggerAdapter
{
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;

    /**
     * @var LoggerService
     */
    private $logger;

    /**
     * @return static
     */
    public static function create(LoggerService $logger)
    {
        $instance = static::getInstance();
        $instance->logger = $logger;

        return $instance;
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
