<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Customer;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\ApiKey\ApiKeyAuthService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\Interfaces\AuthorizationService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Customer\CustomerService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\CustomerReference\CustomerReferenceService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\CustomerReference\Model\CustomerReference;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Customer;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\OrgToken\ProxyDataProvider;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Proxy;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\ORM\Interfaces\RepositoryInterface;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpClient;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpResponse;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\QueryFilter\Operators;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\QueryFilter\QueryFilter;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\RepositoryRegistry;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\BaseTestWithServices;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\ORM\MemoryRepository;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\TestHttpClient;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestServiceRegister;

class CustomerServiceTest extends BaseTestWithServices
{
    /**
     * @var TestHttpClient
     */
    public $httpClient;
    /**
     * @var ProxyDataProvider
     */
    public $proxyTransformer;
    /**
     * @var CustomerService
     */
    private $customerService;
    /**
     * @var RepositoryInterface
     */
    private $customerReferenceRepository;

    public function setUp()
    {
        parent::setUp();

        $me = $this;

        RepositoryRegistry::registerRepository(CustomerReference::CLASS_NAME, MemoryRepository::getClassName());

        $this->httpClient = new TestHttpClient();
        $this->proxyTransformer = new ProxyDataProvider();
        TestServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () use ($me) {
                return $me->httpClient;
            }
        );
        TestServiceRegister::registerService(
            Proxy::CLASS_NAME,
            function () use ($me) {
                return new Proxy($me->shopConfig, $me->httpClient, $me->proxyTransformer);
            }
        );

        TestServiceRegister::registerService(
            CustomerReferenceService::CLASS_NAME,
            function () {
                return CustomerReferenceService::getInstance();
            }
        );

        TestServiceRegister::registerService(
            AuthorizationService::CLASS_NAME,
            function () {
                return ApiKeyAuthService::getInstance();
            }
        );

        $this->shopConfig->setAuthorizationToken('test_token');
        $this->shopConfig->setTestMode(true);
        $this->customerService = CustomerService::getInstance();
        $this->customerReferenceRepository = RepositoryRegistry::getRepository(CustomerReference::CLASS_NAME);
    }

    public function tearDown()
    {
        CustomerService::resetInstance();

        parent::tearDown();
    }

    /**
     * @return void
     * @throws UnprocessableEntityRequestException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function testCreateCustomer()
    {
        $shopReference = 'test_customer_reference';
        $customer = Customer::fromArray(array(
            "name" => "Customer A",
            "email" => "customer@example.org",
        ));
        $this->httpClient->setMockResponses(array($this->getMockCustomerResponse()));

        $mollieReference = $this->customerService->createCustomer($customer, $shopReference);

        $apiRequestHistory = $this->httpClient->getHistory();
        $expectedBody = $this->proxyTransformer->transformCustomer($customer);
        $expectedBody['testmode'] = true;
        $this->assertCount(1, $apiRequestHistory);
        $this->assertEquals('Authorization: Bearer test_token', $apiRequestHistory[0]['headers']['token']);
        $this->assertContains('/customers', $apiRequestHistory[0]['url']);
        $this->assertEquals(json_encode($expectedBody), $apiRequestHistory[0]['body']);
        $this->assertNotNull($mollieReference);
    }

    /**
     * @return HttpResponse
     */
    protected function getMockCustomerResponse()
    {
        $response = file_get_contents(__DIR__ . '/../Common/ApiResponses/customerResponse.json');

        return new HttpResponse(200, array(), $response);
    }

    /**
     * @return void
     * @throws UnprocessableEntityRequestException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws QueryFilterInvalidParamException
     */
    public function testCreateCustomerAddsCustomerReference()
    {
        $shopReference = 'test_customer_reference';
        $customer = Customer::fromArray(array(
            "name" => "Customer A",
            "email" => "customer@example.org",
        ));
        $this->httpClient->setMockResponses(array($this->getMockCustomerResponse()));

        $mollieReference = $this->customerService->createCustomer($customer, $shopReference);

        $queryFilter = new QueryFilter();
        $queryFilter->where('shopReference', Operators::EQUALS, $shopReference);
        /** @var CustomerReference[] $savedCustomerReferences */
        $savedCustomerReferences = $this->customerReferenceRepository->select($queryFilter);
        $this->assertCount(1, $savedCustomerReferences);
        $this->assertEquals($shopReference, $savedCustomerReferences[0]->getShopReference());
        $this->assertEquals($mollieReference, $savedCustomerReferences[0]->getMollieReference());
    }

    /**
     * @return void
     * @throws UnprocessableEntityRequestException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws QueryFilterInvalidParamException
     */
    public function testCreateInvalidCustomer()
    {
        $shopReference = 'test_customer_reference';
        $customer = Customer::fromArray(array(
            'name' => 'Marko',
            'email' => 'test.com'
        ));
        $this->httpClient->setMockResponses(array($this->getMockInvalidResponse()));

        $mollieReference = $this->customerService->createCustomer($customer, $shopReference);

        $queryFilter = new QueryFilter();
        $queryFilter->where('shopReference', Operators::EQUALS, $shopReference);
        /** @var CustomerReference[] $savedCustomerReferences */
        $savedCustomerReferences = $this->customerReferenceRepository->select($queryFilter);
        $this->assertCount(0, $savedCustomerReferences);
        $this->assertNull($mollieReference);
    }

    /**
     * @return HttpResponse
     */
    protected function getMockInvalidResponse()
    {
        $response = file_get_contents(__DIR__ . '/../Common/ApiResponses/invalidResponse.json');

        return new HttpResponse(422, array(), $response);
    }

    /**
     * @return void
     * @throws UnprocessableEntityRequestException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws QueryFilterInvalidParamException
     */
    public function testCustomerCreationExistingCustomerReference()
    {
        $shopReference = 'test_customer_reference';
        $customer = Customer::fromArray(array(
            "name" => "Customer A",
            "email" => "customer@example.org",
        ));
        $this->httpClient->setMockResponses(array($this->getMockCustomerResponse()));
        $this->customerService->createCustomer($customer, $shopReference);

        $this->httpClient->setMockResponses(array($this->getMockCustomerResponse()));
        $mollieReference = $this->customerService->createCustomer($customer, $shopReference);

        $queryFilter = new QueryFilter();
        $queryFilter->where('shopReference', Operators::EQUALS, $shopReference);
        /** @var CustomerReference[] $savedCustomerReferences */
        $savedCustomerReferences = $this->customerReferenceRepository->select($queryFilter);
        $this->assertCount(1, $savedCustomerReferences);
        $this->assertEquals($shopReference, $savedCustomerReferences[0]->getShopReference());
        $this->assertEquals($mollieReference, $savedCustomerReferences[0]->getMollieReference());
    }

    /**
     * @return void
     */
    public function testGetSavedCustomerIdWhenReferenceNotFound()
    {
        $shopReference = 'unknown_reference';

        $mollieReference = $this->customerService->getSavedCustomerId($shopReference);
        $this->assertNull($mollieReference);
    }

    /**
     * @return void
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     */
    public function testRemoveCustomer()
    {
        $shopReference = 'test_customer_reference';
        $customer = Customer::fromArray(array(
            "name" => "Customer A",
            "email" => "customer@example.org",
        ));
        $this->httpClient->setMockResponses(array($this->getMockCustomerResponse()));
        $this->customerService->createCustomer($customer, $shopReference);

        $this->httpClient->setMockResponses(array($this->getMockCustomerResponse()));
        $this->customerService->removeCustomer($shopReference);

        $mollieReference = $this->customerService->getSavedCustomerId($shopReference);
        $this->assertNull($mollieReference);
    }
}
