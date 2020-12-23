<?php
/**
 * @noinspection PhpUnhandledExceptionInspection
 */

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\UI\Controllers;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\WebsiteProfile;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\OrgToken\ProxyDataProvider;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Proxy;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\PaymentMethodService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\UI\Controllers\WebsiteProfileController;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpClient;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpResponse;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\BaseTestWithServices;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\MockPaymentMethodService;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\TestHttpClient;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestServiceRegister;

class WebsiteProfileControllerTest extends BaseTestWithServices
{
    /**
     * @var TestHttpClient
     */
    public $httpClient;
    /**
     * @var WebsiteProfileController
     */
    private $websiteProfileController;
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

        $this->websiteProfileController = new WebsiteProfileController();
        $this->paymentMethodService = MockPaymentMethodService::getInstance();
    }

    public function tearDown()
    {
        MockPaymentMethodService::resetInstance();

        parent::tearDown();
    }

    public function testGetAllProfiles()
    {
        $this->httpClient->setMockResponses(array($this->getMockProfiles()));
        $this->shopConfig->setAuthorizationToken('test_token');
        $this->shopConfig->setTestMode(true);

        $result = $this->websiteProfileController->getAll();

        $lastRequest = $this->httpClient->getLastRequest();
        $this->assertCount(2, $result);
        $this->assertEquals('pfl_htsmhPNGw3', $result[0]->getId());
        $this->assertEquals('Test website profile', $result[0]->getName());
        $this->assertNotEmpty($lastRequest);
        $this->assertEquals('Authorization: Bearer test_token', $lastRequest['headers']['token']);
        $this->assertContains('testmode=true', $lastRequest['url']);
    }

    public function testGetAllProfilesForEmptyToken()
    {
        $result = $this->websiteProfileController->getAll();

        $this->assertCount(0, $result);
    }

    public function testGetAllProfilesIsCachedPerInstance()
    {
        $this->httpClient->setMockResponses(array($this->getMockProfiles()));
        $this->shopConfig->setAuthorizationToken('test_token');
        $this->shopConfig->setTestMode(true);

        $this->websiteProfileController->getAll();
        $this->websiteProfileController->getAll();

        $this->assertCount(1, $this->httpClient->getHistory());
    }

    public function testGettingCurrentProfile()
    {
        $this->shopConfig->setWebsiteProfile(WebsiteProfile::fromArray(array(
            'resource' => 'profile',
            'id' => 'test_dfklasjio11231',
            'name' => 'Test profile',
        )));

        $currentProfile = $this->websiteProfileController->getCurrent();

        $this->assertEquals('test_dfklasjio11231', $currentProfile->getId());
    }

    public function testGettingWebsiteProfileDefault()
    {
        $this->httpClient->setMockResponses(array($this->getMockProfiles()));
        $this->shopConfig->setAuthorizationToken('test_token');
        $this->shopConfig->setTestMode(true);

        $currentProfile = $this->websiteProfileController->getCurrent();

        $this->assertNotNull($currentProfile);
        $this->assertEquals('pfl_htsmhPNGw3', $currentProfile->getId());
    }

    public function testSavingWebsiteProfile()
    {
        $testProfile = WebsiteProfile::fromArray(array(
            'resource' => 'profile',
            'id' => 'test_dfklasjio11231',
            'name' => 'Test profile',
        ));

        $this->websiteProfileController->save($testProfile);

        $this->assertEquals($testProfile, $this->websiteProfileController->getCurrent());
    }

    public function testSavingDefaultProfileValue()
    {
        $this->httpClient->setMockResponses(array($this->getMockProfiles()));
        $this->shopConfig->setAuthorizationToken('test_token');
        $this->shopConfig->setTestMode(true);

        $this->websiteProfileController->save(null);

        $savedProfile = $this->shopConfig->getWebsiteProfile();
        $this->assertNotNull($savedProfile);
        $this->assertEquals('pfl_htsmhPNGw3', $savedProfile->getId());
    }

    public function testSavingDefaultProfileValueWithEmptyProfilesConfiguration()
    {
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), '"{profiles": []}')));
        $this->shopConfig->setAuthorizationToken('test_token');
        $this->shopConfig->setTestMode(true);

        $this->websiteProfileController->save(null);

        $clearPaymentsCallHistory = $this->paymentMethodService->getCallHistory('clearAllOther');
        $savedProfile = $this->shopConfig->getWebsiteProfile();
        $this->assertNull($savedProfile);
        $this->assertCount(1, $clearPaymentsCallHistory);
        $this->assertSame('', $clearPaymentsCallHistory[0]['profileId']);
    }

    public function testChangingWebsiteProfileClearsPaymentMethodConfiguration()
    {
        $oldProfileId = 'test_dfklasjio11231';
        $this->websiteProfileController->save(
            WebsiteProfile::fromArray(
                array(
                    'resource' => 'profile',
                    'id' => $oldProfileId,
                    'name' => 'Test profile',
                )
            )
        );

        $this->websiteProfileController->save(
            WebsiteProfile::fromArray(
                array(
                    'resource' => 'profile',
                    'id' => 'test_changed',
                    'name' => 'Test different profile',
                )
            )
        );

        $clearPaymentsCallHistory = $this->paymentMethodService->getCallHistory('clearAllOther');
        $this->assertCount(2, $clearPaymentsCallHistory);
        $this->assertEquals($oldProfileId, $clearPaymentsCallHistory[0]['profileId']);
    }

    public function testInitialWebsiteProfileSavingDoesNotTriggerProfileClearing()
    {
        $this->websiteProfileController->save(
            WebsiteProfile::fromArray(
                array(
                    'resource' => 'profile',
                    'id' => 'test_dfklasjio11231',
                    'name' => 'Test profile',
                )
            )
        );

        $this->assertCount(0, $this->paymentMethodService->getCallHistory('clear'));
    }

    protected function getMockProfiles()
    {
        $response = file_get_contents(__DIR__ . '/../../Common/ApiResponses/websiteProfiles.json');
        return new HttpResponse(200, array(), $response);
    }
}
