<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Authorization;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\Interfaces\AuthorizationService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\OrgToken\OrgToken;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\OrgToken\OrgTokenAuthService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\WebsiteProfile;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\OrgToken\ProxyDataProvider;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Proxy;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\PaymentMethodService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpClient;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpResponse;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\BaseTestWithServices;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\MockPaymentMethodService;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\TestHttpClient;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestServiceRegister;

/**
 * Class OrgTokenAuthService
 *
 * @package Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Authorization
 */
class OrgTokenAuthServiceTest extends BaseTestWithServices
{
    /**
     * @var OrgTokenAuthService
     */
    private $authService;
    /**
     * @var TestHttpClient
     */
    public $httpClient;
    /**
     * @var MockPaymentMethodService
     */
    private $paymentMethodService;

    public function setUp()
    {
        parent::setUp();

        $me = $this;

        $this->httpClient = new TestHttpClient();
        TestServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () use ($me) {
                return $me->httpClient;
            }
        );
        TestServiceRegister::registerService(
            Proxy::CLASS_NAME,
            function () use ($me) {
                return new Proxy($me->shopConfig, $me->httpClient, new ProxyDataProvider());
            }
        );
        TestServiceRegister::registerService(
            PaymentMethodService::CLASS_NAME,
            function () {
                return MockPaymentMethodService::getInstance();
            }
        );

        TestServiceRegister::registerService(
            AuthorizationService::CLASS_NAME,
            function () use ($me) {
                return OrgTokenAuthService::getInstance();
            }
        );


        $this->authService = OrgTokenAuthService::getInstance();
        $this->paymentMethodService = MockPaymentMethodService::getInstance();
    }

    protected function tearDown()
    {
        parent::tearDown();
        OrgTokenAuthService::resetInstance();
        MockPaymentMethodService::resetInstance();
    }

    public function testAuthorizationTokenValidation()
    {
        $this->httpClient->setMockResponses(array($this->getMockPermissions()));
        $token = new OrgToken('test_token', true);

        $result = $this->authService->validateToken($token);

        $lastRequest = $this->httpClient->getLastRequest();
        $this->assertTrue($result);
        $this->assertNotEmpty($lastRequest);
        $this->assertEquals('Authorization: Bearer test_token', $lastRequest['headers']['token']);
        $this->assertNotContains('testmode=true', $lastRequest['url']);
    }

    public function testAuthorizationTokenValidationWithInsufficientPermissions()
    {
        $this->httpClient->setMockResponses(array($this->getMockInsufficientPermissions()));

        $result = $this->authService->validateToken(new OrgToken('test_token', true));

        $lastRequest = $this->httpClient->getLastRequest();
        $this->assertFalse($result);
        $this->assertNotEmpty($lastRequest);
        $this->assertEquals('Authorization: Bearer test_token', $lastRequest['headers']['token']);
        $this->assertNotContains('testmode=true', $lastRequest['url']);
    }

    public function testAuthorizationTokenValidationFailure()
    {
        $response = new HttpResponse(401, array(), '{ "status": 401, "title": "Unauthorized Request" }');
        $this->httpClient->setMockResponses(array($response));

        $result = $this->authService->validateToken(new OrgToken('test_token', false));

        $lastRequest = $this->httpClient->getLastRequest();
        $this->assertFalse($result);
        $this->assertNotEmpty($lastRequest);
        $this->assertEquals('Authorization: Bearer test_token', $lastRequest['headers']['token']);
        $this->assertNotContains('testmode=true', $lastRequest['url']);
    }

    public function testAuthorizationTokenValidationConfigServiceCleanup()
    {
        $this->httpClient->setMockResponses(array($this->getMockPermissions()));

        $this->authService->validateToken(new OrgToken('test_token', true));

        $this->assertNull($this->shopConfig->getAuthorizationToken());
        $this->assertFalse($this->shopConfig->isTestMode());
    }

    public function testResetting()
    {
        $profileId = 'test_dfklasjio11231';
        $this->shopConfig->setContext('test_context');
        $this->shopConfig->setAuthorizationToken('test_token');
        $this->shopConfig->setTestMode(true);
        $this->shopConfig->setWebsiteProfile(
            WebsiteProfile::fromArray(array(
                'resource' => 'profile',
                'id' => $profileId,
                'name' => 'Test profile',
            ))
        );

        $this->authService->reset();

        $clearPaymentsCallHistory = $this->paymentMethodService->getCallHistory('clear');
        $this->assertCount(1, $clearPaymentsCallHistory);
        $this->assertEquals($profileId, $clearPaymentsCallHistory[0]['profileId']);
        $this->assertNull($this->shopConfig->getAuthorizationToken());
        $this->assertFalse($this->shopConfig->isTestMode());
        $this->assertNull($this->shopConfig->getWebsiteProfile());
    }

    protected function getMockPermissions()
    {
        $response = file_get_contents(__DIR__ . '/../Common/ApiResponses/permissions.json');

        return new HttpResponse(200, array(), $response);
    }

    protected function getMockInsufficientPermissions()
    {
        $response = file_get_contents(__DIR__ . '/../Common/ApiResponses/insufficientPermissions.json');

        return new HttpResponse(200, array(), $response);
    }
}
