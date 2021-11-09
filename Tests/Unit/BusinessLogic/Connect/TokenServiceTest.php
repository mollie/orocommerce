<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Connect;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\Interfaces\AuthorizationService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Connect\DTO\AuthInfo;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Connect\TokenProxy;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Connect\TokenService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\OrgToken\ProxyDataProvider;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Proxy;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpClient;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpResponse;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\BaseTestWithServices;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\MockAuthorizationService;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\TestHttpClient;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestServiceRegister;

/**
 * Class TokenProxyTest
 * @package Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Connect
 */
class TokenServiceTest extends BaseTestWithServices
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
                return new Proxy($self->shopConfig, $self->httpClient, new ProxyDataProvider());
            }
        );

        TestServiceRegister::registerService(
            AuthorizationService::CLASS_NAME,
            function () {
                return new MockAuthorizationService();
            }
        );

        TestServiceRegister::registerService(
            TokenProxy::CLASS_NAME,
            function () use ($self) {
                return new TokenProxy($self->shopConfig, $self->httpClient, new ProxyDataProvider());
            }
        );

        TestServiceRegister::registerService(
            TokenService::CLASS_NAME,
            function () use ($self) {
                return new TokenService(new TokenProxy($self->shopConfig, $self->httpClient, new ProxyDataProvider()));
            }
        );
    }

    /**
     * Test generate tokens from given code
     *
     * @throws UnprocessableEntityRequestException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function testGenerateTokens()
    {
        $response = file_get_contents(__DIR__ . '/../Common/ApiResponses/mollieTokens.json');
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $response)));

        $authInfo = $this->getTokenService()->generate('code_54865asdbviyt6845asdasd');
        $authInfoTest = new AuthInfo(
            'access_TRbHbeB3my8XywBAdT6HRkGAJMuh4',
            'refresh_FS4xc3Mgci2xQ5s5DzaLXh3HhaTZOP',
            $authInfo->getAccessTokenDuration()
        );

        self::assertEquals($authInfo, $authInfoTest);
    }

    /**
     * Test generate tokens from given refreshToken
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     */
    public function testRefreshTokens()
    {
        $response = file_get_contents(__DIR__ . '/../Common/ApiResponses/mollieTokens.json');
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $response)));

        $authInfo = $this->getTokenService()->refreshToken('refresh_54865asdbviyt6845asdasd');
        $authInfoTest = new AuthInfo(
            'access_TRbHbeB3my8XywBAdT6HRkGAJMuh4',
            'refresh_FS4xc3Mgci2xQ5s5DzaLXh3HhaTZOP',
            $authInfo->getAccessTokenDuration()
        );

        self::assertEquals($authInfo, $authInfoTest);
    }

    /**
     * Get TokenService
     *
     * @return TokenService
     */
    private function getTokenService()
    {
        return ServiceRegister::getService(TokenService::CLASS_NAME);
    }
}
