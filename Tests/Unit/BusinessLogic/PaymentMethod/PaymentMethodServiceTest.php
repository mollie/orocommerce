<?php
/**
 * @noinspection PhpUnhandledExceptionInspection
 */

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\PaymentMethod;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Amount;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\PaymentMethod;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Proxy;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\ProxyTransformer;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\ORM\Interfaces\RepositoryInterface;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\PaymentMethodService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpClient;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpResponse;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\RepositoryRegistry;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\BaseTestWithServices;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\ORM\MemoryRepository;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\TestHttpClient;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestServiceRegister;

class PaymentMethodServiceTest extends BaseTestWithServices
{
    /**
     * @var TestHttpClient
     */
    public $httpClient;
    /**
     * @var PaymentMethodService
     */
    private $paymentMethodService;
    /**
     * @var RepositoryInterface
     */
    private $paymentMethodConfigRepository;

    public function setUp()
    {
        parent::setUp();

        $me = $this;

        /** @noinspection PhpUnhandledExceptionInspection */
        RepositoryRegistry::registerRepository(PaymentMethodConfig::CLASS_NAME, MemoryRepository::getClassName());

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

        $this->shopConfig->setAuthorizationToken('test_token');
        $this->shopConfig->setTestMode(true);
        $this->paymentMethodService = PaymentMethodService::getInstance();
        $this->paymentMethodConfigRepository = RepositoryRegistry::getRepository(PaymentMethodConfig::CLASS_NAME);
    }

    public function tearDown()
    {
        PaymentMethodService::resetInstance();

        parent::tearDown();
    }

    public function testGettingAllPaymentMethodConfigurationsMustBeBasedOnMollieState()
    {
        $profileId = 'pfl_htsmhPNGw3';
        $this->httpClient->setMockResponses(array($this->getMockAllPaymentMethods(), $this->getMockEnabledPaymentMethods()));

        $this->paymentMethodService->getAllPaymentMethodConfigurations($profileId);

        $apiRequestHistory = $this->httpClient->getHistory();
        $this->assertCount(2, $apiRequestHistory);
        $this->assertEquals('Authorization: Bearer test_token', $apiRequestHistory[0]['headers']['token']);
        $this->assertContains('testmode=true', $apiRequestHistory[0]['url']);
        $this->assertContains('/methods/all', $apiRequestHistory[0]['url']);
        $this->assertContains("profileId={$profileId}", $apiRequestHistory[0]['url']);
        $this->assertEquals('Authorization: Bearer test_token', $apiRequestHistory[1]['headers']['token']);
        $this->assertContains('/methods', $apiRequestHistory[1]['url']);
        $this->assertContains('testmode=true', $apiRequestHistory[1]['url']);
        $this->assertContains('includeWallets=applepay', $apiRequestHistory[1]['url']);
        $this->assertContains('resource=orders', $apiRequestHistory[1]['url']);
        $this->assertContains("profileId={$profileId}", $apiRequestHistory[1]['url']);
    }

    public function testGettingAllPaymentMethodConfigurationsResultWhenDBIsEmpty()
    {
        $profileId = 'pfl_htsmhPNGw3';
        $this->httpClient->setMockResponses(array($this->getMockAllPaymentMethods(), $this->getMockEnabledPaymentMethods()));

        $result = $this->paymentMethodService->getAllPaymentMethodConfigurations($profileId);

        $this->assertCount(16, $result);
        $this->assertNull($result[0]->getId());
        $this->assertEquals($profileId, $result[0]->getProfileId());
        $this->assertEquals('applepay', $result[0]->getMollieId());
        $this->assertEquals('applepay', $result[0]->getName());
        $this->assertEquals('Apple Pay', $result[0]->getDescription());
        $this->assertEquals(PaymentMethodConfig::API_METHOD_ORDERS, $result[0]->getApiMethod());
        $this->assertEquals(
            'https://www.mollie.com/external/icons/payment-methods/applepay%402x.png',
            $result[0]->getImage()
        );
        $this->assertTrue($result[0]->isEnabled());
        $this->assertEquals('ideal', $result[1]->getMollieId());
        $this->assertFalse($result[1]->isEnabled());
    }

    public function testGettingAllPaymentMethodConfigurationsResultWithExistingDBConfigs()
    {
        $profileId = 'pfl_htsmhPNGw3';
        $this->httpClient->setMockResponses(array($this->getMockAllPaymentMethods(), $this->getMockEnabledPaymentMethods()));
        $this->preparePaymentMethodConfigs($profileId);

        $result = $this->paymentMethodService->getAllPaymentMethodConfigurations($profileId);

        $this->assertCount(16, $result);
        $this->assertNotNull($result[0]->getId());
        $this->assertEquals($profileId, $result[0]->getProfileId());
        $this->assertEquals('applepay', $result[0]->getMollieId());
        $this->assertEquals('Test method name', $result[0]->getName());
        $this->assertEquals('Method description changed by payment configuration', $result[0]->getDescription());
        $this->assertEquals(PaymentMethodConfig::API_METHOD_PAYMENT, $result[0]->getApiMethod());
        $this->assertEquals('/path/to/test/image/name.png', $result[0]->getImage());
        $this->assertTrue(
            $result[0]->isEnabled(),
            'It should not be possible to change payment method status by config setup.'
        );
    }

    public function testGetEnabledPaymentMethods()
    {
        $profileId = 'pfl_htsmhPNGw3';
        $billingCountry = 'DE';
        $amountValue = '123.45';
        $amountCurrency = 'USD';
        $this->httpClient->setMockResponses(array($this->getMockEnabledPaymentMethods()));
        $this->preparePaymentMethodConfigs($profileId);

        $result = $this->paymentMethodService->getEnabledPaymentMethodConfigurations(
            $profileId,
            $billingCountry,
            Amount::fromArray(array('value' => $amountValue, 'currency' => $amountCurrency))
        );

        $apiRequestHistory = $this->httpClient->getHistory();
        $this->assertCount(1, $apiRequestHistory);
        $this->assertEquals('Authorization: Bearer test_token', $apiRequestHistory[0]['headers']['token']);
        $this->assertContains('testmode=true', $apiRequestHistory[0]['url']);
        $this->assertContains('/methods', $apiRequestHistory[0]['url']);
        $this->assertContains("profileId={$profileId}", $apiRequestHistory[0]['url']);
        $this->assertContains("resource=orders", $apiRequestHistory[0]['url']);
        $this->assertContains("billingCountry={$billingCountry}", $apiRequestHistory[0]['url']);
        $this->assertContains(urlencode('amount[value]')."={$amountValue}", $apiRequestHistory[0]['url']);
        $this->assertContains(urlencode('amount[currency]')."={$amountCurrency}", $apiRequestHistory[0]['url']);

        $this->assertCount(4, $result);
    }

    public function testGetEnabledPaymentMethodsWithApiMethodFilter()
    {
        $profileId = 'pfl_htsmhPNGw3';
        $apiMethod = PaymentMethodConfig::API_METHOD_PAYMENT;
        $billingCountry = 'DE';
        $amountValue = '123.45';
        $amountCurrency = 'USD';
        $this->httpClient->setMockResponses(array($this->getMockEnabledPaymentMethods()));
        $this->preparePaymentMethodConfigs($profileId);

        $result = $this->paymentMethodService->getEnabledPaymentMethodConfigurations(
            $profileId,
            $billingCountry,
            Amount::fromArray(array('value' => $amountValue, 'currency' => $amountCurrency)),
            $apiMethod
        );

        $apiRequestHistory = $this->httpClient->getHistory();
        $this->assertCount(1, $apiRequestHistory);
        $this->assertEquals('Authorization: Bearer test_token', $apiRequestHistory[0]['headers']['token']);
        $this->assertContains('testmode=true', $apiRequestHistory[0]['url']);
        $this->assertContains('/methods', $apiRequestHistory[0]['url']);
        $this->assertContains("profileId={$profileId}", $apiRequestHistory[0]['url']);
        $this->assertContains("resource=payments", $apiRequestHistory[0]['url']);
        $this->assertContains("billingCountry={$billingCountry}", $apiRequestHistory[0]['url']);
        $this->assertContains(urlencode('amount[value]')."={$amountValue}", $apiRequestHistory[0]['url']);
        $this->assertContains(urlencode('amount[currency]')."={$amountCurrency}", $apiRequestHistory[0]['url']);

        $this->assertCount(4, $result);
    }

    public function testClearing()
    {
        $profileId = 'pfl_htsmhPNGw3';
        $this->preparePaymentMethodConfigs($profileId);

        $this->paymentMethodService->clear($profileId);

        $this->assertCount(0, $this->paymentMethodConfigRepository->select());
    }

    public function testClearingOther()
    {
        $profileId = 'pfl_htsmhPNGw3';
        $otherProfileId = 'test_profile_id_for_which_config_should_be_removed';
        $this->preparePaymentMethodConfigs($profileId);
        $this->preparePaymentMethodConfigs($otherProfileId);

        $this->paymentMethodService->clearAllOther($profileId);

        /** @var PaymentMethodConfig[] $configsInDB */
        $configsInDB = $this->paymentMethodConfigRepository->select();
        $this->assertCount(2, $configsInDB);
        $this->assertEquals($profileId, $configsInDB[0]->getProfileId());
        $this->assertEquals($profileId, $configsInDB[1]->getProfileId());
    }

    protected function preparePaymentMethodConfigs($profileId)
    {
        $applePayConfig = new PaymentMethodConfig();
        $applePayConfig->setOriginalAPIConfig(
            PaymentMethod::fromArray(
                array(
                    'resource' => 'method',
                    'id' => 'applepay',
                    'description' => 'Apple Pay',
                    'image' =>  array(
                        'size1x' => 'https://www.mollie.com/external/icons/payment-methods/applepay.png',
                        'size2x' => 'https://www.mollie.com/external/icons/payment-methods/applepay%402x.png',
                        'svg' => 'https://www.mollie.com/external/icons/payment-methods/applepay.svg',
                    ),
                )
            )
        );
        $applePayConfig->setName('Test method name');
        $applePayConfig->setDescription('Method description changed by payment configuration');
        $applePayConfig->setProfileId($profileId);
        $applePayConfig->setApiMethod(PaymentMethodConfig::API_METHOD_PAYMENT);
        $applePayConfig->setImage('/path/to/test/image/name.png');

        $idealConfig = new PaymentMethodConfig();
        $idealConfig->setOriginalAPIConfig(
            PaymentMethod::fromArray(
                array(
                    'resource' => 'method',
                    'id' => 'ideal',
                    'description' => 'iDEAL',
                    'image' =>  array(
                        'size1x' => 'https://www.mollie.com/external/icons/payment-methods/ideal.png',
                        'size2x' => 'https://www.mollie.com/external/icons/payment-methods/ideal%402x.png',
                        'svg' => 'https://www.mollie.com/external/icons/payment-methods/ideal.svg',
                    ),
                )
            )
        );
        $idealConfig->setProfileId($profileId);

        $this->paymentMethodConfigRepository->save($applePayConfig);
        $this->paymentMethodConfigRepository->save($idealConfig);
    }

    protected function getMockAllPaymentMethods()
    {
        $response = file_get_contents(__DIR__ . '/../Common/ApiResponses/paymentMethodsAll.json');
        return new HttpResponse(200, array(), $response);
    }

    protected function getMockEnabledPaymentMethods()
    {
        $response = file_get_contents(__DIR__ . '/../Common/ApiResponses/paymentMethodsEnabled.json');
        return new HttpResponse(200, array(), $response);
    }
}
