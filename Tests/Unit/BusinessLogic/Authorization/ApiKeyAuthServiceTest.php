<?php


namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Authorization;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\ApiKey\ApiKey;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\ApiKey\ApiKeyAuthService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\Interfaces\AuthorizationService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\ApiKey\ProxyDataProvider;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Proxy;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpClient;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpResponse;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\BaseTestWithServices;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\TestHttpClient;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestServiceRegister;

/**
 * Class ApiKeyAuthServiceTest
 *
 * @package Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Authorization
 */
class ApiKeyAuthServiceTest extends BaseTestWithServices
{
    /**
     * @var ApiKeyAuthService
     */
    private $authService;
    /**
     * @var TestHttpClient
     */
    public $httpClient;


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
            AuthorizationService::CLASS_NAME,
            function () use ($me) {
                return ApiKeyAuthService::getInstance();
            }
        );


        $this->authService = ApiKeyAuthService::getInstance();
    }

    protected function tearDown()
    {
        parent::tearDown();
        ApiKeyAuthService::resetInstance();
    }

    public function testConnection()
    {
        $this->httpClient->setMockResponses(array($this->getMockProfile(), $this->getMockProfile()));
        $apiKey = new ApiKey('test_vzCV9AHCDpwuAMfEPtWz3Pah7RqA7j');
        $this->authService->connect($apiKey);

        $lastRequest = $this->httpClient->getLastRequest();
        $this->assertNotEmpty($lastRequest);
        $this->assertEquals('Authorization: Bearer test_vzCV9AHCDpwuAMfEPtWz3Pah7RqA7j', $lastRequest['headers']['token']);
        $this->assertNotContains('testmode=true', $lastRequest['url']);
    }

    public function testConnectionWithInvalidKey()
    {
        $this->httpClient->setMockResponses(array(new HttpResponse(401, array(), '')));
        $apiKey = new ApiKey('test_vzCV9AHCDpwuAMfEPtWz3Pah7RqA7j');
        $this->expectException('Exception');
        $this->authService->connect($apiKey);
    }

    public function testAuthorizationTokenValidation()
    {
        $this->httpClient->setMockResponses(array($this->getMockProfile()));

        $apiKey = new ApiKey('test_vzCV9AHCDpwuAMfEPtWz3Pah7RqA7j');
        $result = $this->authService->validateToken($apiKey);

        $lastRequest = $this->httpClient->getLastRequest();
        $this->assertTrue($result);
        $this->assertNotEmpty($lastRequest);
        $this->assertEquals('Authorization: Bearer test_vzCV9AHCDpwuAMfEPtWz3Pah7RqA7j', $lastRequest['headers']['token']);
        $this->assertNotContains('testmode=true', $lastRequest['url']);
    }

    public function testApiKeyTestMode()
    {
        $apiKey = new ApiKey('test_vzCV9AHCDpwuAMfEPtWz3Pah7RqA7j');
        $this->assertTrue($apiKey->isTest());
    }


    public function testApiKeyNotInValidFormat()
    {
        $this->expectException('InvalidArgumentException');
        $this->authService->validateToken(new ApiKey('fajsdkljf'));
    }

    private function getMockProfile()
    {
        $response = file_get_contents(__DIR__ . '/../Common/ApiResponses/currentProfile.json');

        return new HttpResponse(200, array(), $response);
    }
}
