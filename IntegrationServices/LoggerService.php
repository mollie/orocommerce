<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationServices;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\Logger as CoreLogger;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\LogData;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;
use Psr\Log\LoggerInterface;

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

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Log message in system
     *
     * @param \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\LogData $data
     */
    public function logMessage(LogData $data)
    {
        /** @var Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        $minLogLevel = $configService->getMinLogLevel();
        $logLevel = $data->getLogLevel();

        if (($logLevel > $minLogLevel) && !$configService->isDebugModeEnabled()) {
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