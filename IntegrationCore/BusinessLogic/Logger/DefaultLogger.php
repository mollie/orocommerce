<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Logger;


use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Proxy;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\ProxyTransformer;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpClient;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\Interfaces\DefaultLoggerAdapter;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\LogData;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;

class DefaultLogger implements DefaultLoggerAdapter
{
    /**
     * Log message in system.
     *
     * @param LogData $data
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     */
    public function logMessage(LogData $data)
    {
        /** @var Configuration $config */
        $config = ServiceRegister::getService(Configuration::CLASS_NAME);
        /** @var HttpClient $client */
        $client = ServiceRegister::getService(HttpClient::CLASS_NAME);
        /** @var ProxyTransformer $transformer */
        $transformer = ServiceRegister::getService(ProxyTransformer::CLASS_NAME);
        $proxy = new Proxy($config, $client, $transformer);

        $proxy->createLog($data);
    }
}