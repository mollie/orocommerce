<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\TaskExecution;

use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpClient;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpResponse;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\LoggingHttpClient;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\BaseInfrastructureTestWithServices;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\TestHttpClient;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestServiceRegister;

class LoggingHttpClientTest extends BaseInfrastructureTestWithServices
{
    /**
     * @var TestHttpClient
     */
    protected $httpClient;
    /**
     * @var \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\LoggingHttpClient
     */
    protected $loggingHttpClient;

    protected function setUp()
    {
        parent::setUp();

        $this->httpClient = new TestHttpClient();
        $this->loggingHttpClient = new LoggingHttpClient($this->httpClient);
        $testCasedInstance = $this;
        TestServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () use ($testCasedInstance) {
                return $testCasedInstance->loggingHttpClient;
            }
        );
    }

    /**
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpCommunicationException
     */
    public function testLoggingHttpClientLogsRequestAndResponseData()
    {
        $response = new HttpResponse(200, array(), '{}');
        $this->httpClient->setMockResponses(array($response));

        $this->loggingHttpClient->request('GET', 'example.com/test', array('test' => 'test'), 'request body');

        $this->assertTrue($this->shopLogger->isMessageContainedInLog('Sending http request to example.com/test'));
        $this->assertTrue($this->shopLogger->isMessageContainedInLog('Http response from example.com/test'));
    }

    public function testLoggingHttpClientLogsAsyncRequestData()
    {
        $response = new HttpResponse(200, array(), '{}');
        $this->httpClient->setMockResponses(array($response));

        $this->loggingHttpClient->requestAsync('GET', 'example.com/test', array('test' => 'test'), 'request body');

        $this->assertTrue($this->shopLogger->isMessageContainedInLog('Sending async http request to example.com/test'));
    }
}
