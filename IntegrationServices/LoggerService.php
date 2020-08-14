<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationServices;

use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Configuration\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\Logger as CoreLogger;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\LogData;
use Psr\Log\LoggerInterface;

/**
 * Class LoggerService
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationServices
 */
class LoggerService
{
    /**
     * Log level names for corresponding log level codes.
     *
     * @var array
     */
    protected static $logLevelName = array(
        CoreLogger::ERROR => 'ERROR',
        CoreLogger::WARNING => 'WARNING',
        CoreLogger::INFO => 'INFO',
        CoreLogger::DEBUG => 'DEBUG',
    );

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    /**
     * @var Configuration
     */
    private $configService;

    /**
     * LoggerService constructor.
     *
     * @param LoggerInterface $logger
     * @param Configuration $configService
     */
    public function __construct(LoggerInterface $logger, Configuration $configService)
    {
        $this->logger = $logger;
        $this->configService = $configService;
    }

    /**
     * Log message in system
     *
     * @param \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\LogData $data
     */
    public function logMessage(LogData $data)
    {
        $minLogLevel = $this->configService->getMinLogLevel();
        $logLevel = $data->getLogLevel();

        if (($logLevel > $minLogLevel) && !$this->configService->isDebugModeEnabled()) {
            return;
        }

        $message = 'MOLLIE LOG:' . ' | '
            . 'Date: ' . date('d/m/Y') . ' | '
            . 'Time: ' . date('H:i:s') . ' | '
            . 'Log level: ' . self::$logLevelName[$logLevel] . ' | '
            . 'Message: ' . $data->getMessage();
        $message .= "\n";

        $contextData = array();
        $context = $data->getContext();
        if (!empty($context)) {
            foreach ($context as $item) {
                $contextData[$item->getName()] = $item->getValue();
            }
        }

        switch ($logLevel) {
            case CoreLogger::ERROR:
                $this->logger->error($message, $contextData);
                break;
            case CoreLogger::WARNING:
            case CoreLogger::INFO:
            case CoreLogger::DEBUG:
                $this->logger->warning($message, $contextData);
        }
    }
}
