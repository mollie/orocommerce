<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\Interfaces;

use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\LogData;

/**
 * Interface LoggerAdapter.
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\Interfaces
 */
interface LoggerAdapter
{
    /**
     * Log message in system
     *
     * @param LogData $data
     */
    public function logMessage(LogData $data);
}
