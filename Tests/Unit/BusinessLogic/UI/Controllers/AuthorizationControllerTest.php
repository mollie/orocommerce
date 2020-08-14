<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\UI\Controllers;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\WebsiteProfile;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Proxy;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\ProxyTransformer;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\PaymentMethodService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\UI\Controllers\AuthorizationController;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpClient;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpResponse;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\BaseTestWithServices;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\MockPaymentMethodService;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\TestHttpClient;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestServiceRegister;

class AuthorizationControllerTest extends BaseTestWithServices
{
    /**
     * @var AuthorizationController
     */
    private $authController;
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
                return new Proxy($me->shopConfig, $me->httpClient, new ProxyTransformer());
            }
        );
        TestServiceRegister::registerService(
            PaymentMethodService::CLASS_NAME,
            function () {
                return MockPaymentMethodService::getInstance();
            }
        );


        $this->authController = new AuthorizationController();
        $this->paymentMethodService = MockPaymentMethodService::getInstance();
    }

    public function testAuthorizationTokenValidation()
    {
        $this->httpClient->setMockResponses(array($this->getMockPermissions()));
        $token = 'test_token';
        $testMode = true;

        $result = $this->authController->validateToken($token, $testMode);

        $lastRequest = $this->httpClient->getLastRequest();
        $this->assertTrue($result);
        $this->assertNotEmpty($lastRequest);
        $this->assertEquals('Authorization: Bearer test_token', $lastRequest['headers']['token']);
        $this->assertNotContains('testmode=true', $lastRequest['url']);
    }

    public function testAuthorizationTokenValidationWithInsufficientPermissions()
    {
        $this->httpClient->setMockResponses(array($this->getMockInsufficientPermissions()));
        $token = 'test_token';
        $testMode = true;

        $result = $this->authController->validateToken($token, $testMode);

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
        $token = 'test_token';
        $testMode = false;

        $result = $this->authController->validateToken($token, $testMode);

        $lastRequest = $this->httpClient->getLastRequest();
        $this->assertFalse($result);
        $this->assertNotEmpty($lastRequest);
        $this->assertEquals('Authorization: Bearer test_token', $lastRequest['headers']['token']);
        $this->assertNotContains('testmode=true', $lastRequest['url']);
    }

    public function testAuthorizationTokenValidationConfigServiceCleanup()
    {
        $this->httpClient->setMockResponses(array($this->getMockPermissions()));
        $token = 'test_token';
        $testMode = true;

        $this->authController->validateToken($token, $testMode);

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

        $this->authController->reset();

        $clearPaymentsCallHistory = $this->paymentMethodService->getCallHistory('clear');
        $this->assertCount(1, $clearPaymentsCallHistory);
        $this->assertEquals($profileId, $clearPaymentsCallHistory[0]['profileId']);
        $this->assertNull($this->shopConfig->getAuthorizationToken());
        $this->assertFalse($this->shopConfig->isTestMode());
        $this->assertNull($this->shopConfig->getWebsiteProfile());
    }

    protected function getMockPermissions()
    {
        $response = file_get_contents(__DIR__ . '/../../Common/ApiResponses/permissions.json');
        return new HttpResponse(200, array(), $response);
    }

    protected function getMockInsufficientPermissions()
    {
        $response = file_get_contents(__DIR__ . '/../../Common/ApiResponses/insufficientPermissions.json');
        return new HttpResponse(200, array(), $response);
    }
}
