<?php
/**
 * @noinspection PhpUnhandledExceptionInspection
 */

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\UI\Controllers;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\PaymentMethod;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\PaymentMethodService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\UI\Controllers\PaymentMethodController;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\QueryFilter\Operators;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\QueryFilter\QueryFilter;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\RepositoryRegistry;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\BaseTestWithServices;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\MockPaymentMethodService;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\ORM\MemoryRepository;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestServiceRegister;

class PaymentMethodControllerTest extends BaseTestWithServices
{
    /**
     * @var PaymentMethodController
     */
    private $paymentMethodController;
    /**
     * @var MockPaymentMethodService
     */
    private $paymentMethodService;

    public function setUp()
    {
        parent::setUp();

        TestServiceRegister::registerService(
            PaymentMethodService::CLASS_NAME,
            function () {
                return MockPaymentMethodService::getInstance();
            }
        );

        RepositoryRegistry::registerRepository(PaymentMethodConfig::CLASS_NAME, MemoryRepository::getClassName());

        $this->paymentMethodController = new PaymentMethodController();
        $this->paymentMethodService = MockPaymentMethodService::getInstance();
    }

    public function tearDown()
    {
        MockPaymentMethodService::resetInstance();

        parent::tearDown();
    }

    public function testGetAllPaymentMethods()
    {
        $profileId = 'test_profile_id';
        $this->preparePaymentMethodConfigs($profileId);

        $result = $this->paymentMethodController->getAll($profileId);

        $this->assertCount(2, $result);
        $this->assertEquals('applepay', $result[0]->getMollieId());
        $this->assertEquals('Test method name', $result[0]->getName());
        $this->assertEquals('ideal', $result[1]->getMollieId());
        $this->assertEquals('ideal', $result[1]->getName());
    }

    public function testGetEnabled()
    {
        $profileId = 'test_profile_id';
        $this->preparePaymentMethodConfigs($profileId);

        $result = $this->paymentMethodController->getEnabled($profileId, 'DE');

        $serviceCallHistory = $this->paymentMethodService->getCallHistory('getEnabledPaymentMethodConfigurations');
        $this->assertCount(2, $result);
        $this->assertCount(1, $serviceCallHistory);
        $this->assertEquals('applepay', $result[0]->getMollieId());
        $this->assertEquals('Test method name', $result[0]->getName());
        $this->assertEquals('ideal', $result[1]->getMollieId());
        $this->assertEquals('ideal', $result[1]->getName());
        $this->assertEquals($profileId, $serviceCallHistory[0]['profileId']);
        $this->assertEquals('DE', $serviceCallHistory[0]['billingCountry']);
        $this->assertEquals(null, $serviceCallHistory[0]['amount']);
        $this->assertEquals(PaymentMethodConfig::API_METHOD_ORDERS, $serviceCallHistory[0]['apiMethod']);
    }

    public function testGetEnabledWithSpecificPaymentMethod()
    {
        $profileId = 'test_profile_id';

        $this->paymentMethodController->getEnabled($profileId, 'DE', null, PaymentMethodConfig::API_METHOD_PAYMENT);

        $serviceCallHistory = $this->paymentMethodService->getCallHistory('getEnabledPaymentMethodConfigurations');
        $this->assertCount(1, $serviceCallHistory);
        $this->assertEquals($profileId, $serviceCallHistory[0]['profileId']);
        $this->assertEquals('DE', $serviceCallHistory[0]['billingCountry']);
        $this->assertEquals(null, $serviceCallHistory[0]['amount']);
        $this->assertEquals(PaymentMethodConfig::API_METHOD_PAYMENT, $serviceCallHistory[0]['apiMethod']);
    }

    public function testSavingPaymentMethods()
    {
        $profileId = 'test_profile_id';
        $paymentMethodConfigs = $this->createPaymentMethodConfigs($profileId);

        $this->paymentMethodController->save($paymentMethodConfigs);

        $savedPaymentMethodConfigs = $this->getSavedPaymentMethodConfigs($profileId);
        $this->assertCount(2, $paymentMethodConfigs);

        $savedConfigData = $savedPaymentMethodConfigs[0]->toArray();
        $this->assertNotNull($savedConfigData['id']);
        $this->assertNotNull($savedPaymentMethodConfigs[1]->getId());
        $this->assertEquals($paymentMethodConfigs[0]->getName(), $savedConfigData['name']);
        $this->assertEquals($paymentMethodConfigs[0]->getDescription(), $savedConfigData['description']);
        $this->assertEquals($paymentMethodConfigs[0]->getApiMethod(), $savedConfigData['apiMethod']);
        $this->assertEquals($paymentMethodConfigs[0]->getImage(), $savedConfigData['image']);
    }

    public function testUpdatingPaymentMethods()
    {
        $profileId = 'test_profile_id';
        $paymentMethodConfigs = $this->createPaymentMethodConfigs($profileId);
        $this->paymentMethodController->save($paymentMethodConfigs);

        $this->paymentMethodController->save($this->getSavedPaymentMethodConfigs($profileId));

        $savedPaymentMethodConfigs = $this->getSavedPaymentMethodConfigs($profileId);
        $this->assertCount(2, $savedPaymentMethodConfigs);

        $savedConfigData = $savedPaymentMethodConfigs[0]->toArray();
        $this->assertNotNull($savedConfigData['id']);
        $this->assertNotNull($savedPaymentMethodConfigs[1]->getId());
        $this->assertEquals($paymentMethodConfigs[0]->getName(), $savedConfigData['name']);
        $this->assertEquals($paymentMethodConfigs[0]->getDescription(), $savedConfigData['description']);
        $this->assertEquals($paymentMethodConfigs[0]->getApiMethod(), $savedConfigData['apiMethod']);
        $this->assertEquals($paymentMethodConfigs[0]->getImage(), $savedConfigData['image']);
    }

    /**
     * @param string $profileId
     *
     * @return PaymentMethodConfig[]
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    protected function getSavedPaymentMethodConfigs($profileId)
    {
        $filter = new QueryFilter();
        $filter->where('profileId', Operators::EQUALS, $profileId);

        /** @var PaymentMethodConfig[] $paymentMethodConfigs */
        $paymentMethodConfigs = RepositoryRegistry::getRepository(PaymentMethodConfig::CLASS_NAME)->select($filter);
        return $paymentMethodConfigs;
    }

    protected function preparePaymentMethodConfigs($profileId)
    {
        $this->paymentMethodService->setUp($this->createPaymentMethodConfigs($profileId));
    }

    /**
     * @param string $profileId
     *
     * @return PaymentMethodConfig[]
     */
    protected function createPaymentMethodConfigs($profileId)
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

        return array($applePayConfig, $idealConfig);
    }
}
