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
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\BaseTestWithServices;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\MockAuthorizationService;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\TestHttpClient;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestServiceRegister;

/**
 * Class AuthorizationServiceTest
 * @package Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Connect
 */
class AuthorizationServiceTest extends BaseTestWithServices
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
            TokenService::CLASS_NAME,
            function () use ($self) {
                return new TokenService(new TokenProxy($self->shopConfig, $self->httpClient, new ProxyDataProvider()));
            }
        );

        TestServiceRegister::registerService(
            AuthorizationService::CLASS_NAME,
            function () {
                return new MockAuthorizationService();
            }
        );
    }

    /**
     * Tests getAuthInfo when accessToken is valid
     *
     * @throws UnprocessableEntityRequestException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function testValidGetAuthInfo()
    {
        $time = new \DateTime('now');
        $time->modify('+ 59 minutes');

        $originalAuthInfo = new AuthInfo('accessToken', 'refreshToken', $time->getTimestamp());
        $this->shopConfig->setAuthorizationInfo(new AuthInfo('accessToken', 'refreshToken', $time->getTimestamp()));

        $authInfo = $this->getAuthorizationService()->getAuthInfo();

        self::assertEquals($originalAuthInfo, $authInfo);
    }

    /**
     * Testing function for getting authorize url
     */
    public function testGettingAuthorizeUrl()
    {
        $authorizeUrl = $this->getAuthorizationService()->getAuthorizeUrl('en_US');

        $params = array(
            'client_id' => 'clientId',
            'redirect_uri' => 'https://test/tets',
            'state' => $this->shopConfig->getStateString(),
            'scope' => 'payments.read organizations.read',
            'response_type' => 'code',
            'approval_prompt' => 'force',
            'locale' => 'en_US',
        );

        $testAuthorizeUrl = 'https://www.mollie.com/oauth2/authorize' . '?' . http_build_query($params);

        self::assertEquals($authorizeUrl, $testAuthorizeUrl);
    }

    /**
     * Returns authorization service
     *
     * @return MockAuthorizationService
     */
    public function getAuthorizationService()
    {
        return ServiceRegister::getService(AuthorizationService::CLASS_NAME);
    }

    /**
     * Returns Token service
     *
     * @return TokenService
     */
    public function getTokenService()
    {
        return ServiceRegister::getService(TokenService::CLASS_NAME);
    }
}
