<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\VersionCheck;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\ApiKey\ApiKeyAuthService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\Interfaces\AuthorizationService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\OrgToken\ProxyDataProvider;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\VersionCheck\Http\VersionCheckProxy;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpClient;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpResponse;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\BaseTestWithServices;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\TestShopConfiguration;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\TestVersionCheckService;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\TestHttpClient;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestServiceRegister;

class VersionCheckServiceTest extends BaseTestWithServices
{
    protected $versionCheckService;
    private $httpClient;

    public function setUp()
    {
        parent::setUp();

        $this->versionCheckService = TestVersionCheckService::getInstance();

        $this->httpClient = new TestHttpClient();
        $me = $this;
        TestServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () use ($me) {
                return $me->httpClient;
            }
        );
        TestServiceRegister::registerService(
            VersionCheckProxy::CLASS_NAME,
            function () use ($me) {
                return new VersionCheckProxy($me->shopConfig, $me->httpClient, new ProxyDataProvider());
            }
        );
        TestServiceRegister::registerService(
            AuthorizationService::CLASS_NAME,
            function () {
                return ApiKeyAuthService::getInstance();
            }
        );
    }

    public function tearDown()
    {
        TestVersionCheckService::resetInstance();

        parent::tearDown();
    }

    /**
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function testVersionCheckWhenNewVersionAvailable()
    {
        $this->httpClient->setMockResponses(array($this->getMockNewVersionResults()));
        $this->versionCheckService->checkForNewVersion();

        $this->assertNotEmpty($this->versionCheckService->getCallHistory());
    }

    /**
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function testVersionCheckWhenNewVersionNotAvailable()
    {
        TestShopConfiguration::$CURRENT_INTEGRATION_VERSION = '3.0.0';
        $this->httpClient->setMockResponses(array($this->getMockNewVersionResults()));
        $this->versionCheckService->checkForNewVersion();

        $this->assertEmpty($this->versionCheckService->getCallHistory());
    }

    /**
     * @return HttpResponse
     */
    protected function getMockNewVersionResults()
    {
        $response = file_get_contents(__DIR__ . '/../Common/ApiResponses/version-check.json');

        return new HttpResponse(200, array(), $response);
    }
}
