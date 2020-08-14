<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Http;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Proxy;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\ProxyTransformer;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpClient;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpResponse;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\BaseTestWithServices;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\TestHttpClient;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestServiceRegister;

/**
 * Class ProxyTest.
 *
 * @package Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Http
 */
class ProxyTest extends BaseTestWithServices
{
    /**
     * @var TestHttpClient
     */
    public $httpClient;

    public function setUp()
    {
        parent::setUp();

        $this->httpClient = new TestHttpClient();
        $self = $this;

        TestServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () use ($self) {
                return $self->httpClient;
            }
        );

        TestServiceRegister::registerService(
            Proxy::CLASS_NAME,
            function () use ($self) {
                return new Proxy($self->shopConfig, $self->httpClient, new ProxyTransformer());
            }
        );
    }

    /**
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function testSuccessfulResponse()
    {
        $response = file_get_contents(__DIR__ . '/../Common/ApiResponses/permissions.json');
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $response)));

        $permissions = $this->getProxy()->getAccessTokenPermissions();

        $this->assertCount(22, $permissions);
    }

    /**
     * Tests the case when API returns unprocessable entity error with extra information about failed validation field
     *
     * @expectedException \Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException
     * @expectedExceptionCode 422
     * @expectedExceptionMessage Unprocessable Entity: The amount is higher than the maximum
     *
     * @throws UnprocessableEntityRequestException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function testUnprocessableEntityResponse()
    {
        $response = file_get_contents(__DIR__ . '/../Common/ApiResponses/badResponseWithField.json');
        $this->httpClient->setMockResponses(array(new HttpResponse(422, array(), $response)));

        try {
            $this->getProxy()->getAccessTokenPermissions();
        } catch (UnprocessableEntityRequestException $e) {
            $this->assertEquals('amount', $e->getField());
            throw $e;
        }

        $this->fail('Proxy must throw unprocessable entity exception for status code 422.');
    }

    /**
     * Tests the case when API returns unauthorized response
     *
     * @expectedException \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @expectedExceptionCode 401
     * @expectedExceptionMessage Unauthorized Request: Missing authentication, or failed to authenticate
     *
     * @throws UnprocessableEntityRequestException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function testUnauthorizedResponse()
    {
        $response = file_get_contents(__DIR__ . '/../Common/ApiResponses/unauthorizedResponse.json');
        $this->httpClient->setMockResponses(array(new HttpResponse(401, array(), $response)));

        $this->getProxy()->getAccessTokenPermissions();
    }

    /**
     * Tests the case when API returns unauthorized response
     *
     * @expectedException \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpRequestException
     * @expectedExceptionCode 400
     * @expectedExceptionMessage Unauthorized Request: Missing authentication, or failed to authenticate
     *
     * @throws UnprocessableEntityRequestException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function testGeneralErrorResponse()
    {
        $response = file_get_contents(__DIR__ . '/../Common/ApiResponses/unauthorizedResponse.json');
        $this->httpClient->setMockResponses(array(new HttpResponse(400, array(), $response)));

        $this->getProxy()->getAccessTokenPermissions();
    }

    /**
     * @return Proxy
     */
    private function getProxy()
    {
        /** @var Proxy $proxy */
        $proxy = TestServiceRegister::getService(Proxy::CLASS_NAME);

        return $proxy;
    }
}
