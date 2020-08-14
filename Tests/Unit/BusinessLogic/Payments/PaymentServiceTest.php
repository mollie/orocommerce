<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Payments;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Payment;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Proxy;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\ProxyTransformer;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Model\OrderReference;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\OrderReferenceService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\ORM\Interfaces\RepositoryInterface;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\PaymentMethods;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Payments\PaymentService;
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

class PaymentServiceTest extends BaseTestWithServices
{
    /**
     * @var TestHttpClient
     */
    public $httpClient;
    /**
     * @var ProxyTransformer
     */
    public $proxyTransformer;
    /**
     * @var PaymentService
     */
    private $paymentService;
    /**
     * @var RepositoryInterface
     */
    private $orderReferenceRepository;

    public function setUp()
    {
        parent::setUp();

        $me = $this;

        RepositoryRegistry::registerRepository(OrderReference::CLASS_NAME, MemoryRepository::getClassName());

        $this->httpClient = new TestHttpClient();
        $this->proxyTransformer = new ProxyTransformer();
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
            OrderReferenceService::CLASS_NAME,
            function () {
                return OrderReferenceService::getInstance();
            }
        );

        $this->shopConfig->setAuthorizationToken('test_token');
        $this->shopConfig->setTestMode(true);
        $this->paymentService = PaymentService::getInstance();
        $this->orderReferenceRepository = RepositoryRegistry::getRepository(OrderReference::CLASS_NAME);
    }

    public function tearDown()
    {
        PaymentService::resetInstance();

        parent::tearDown();
    }

    /**
     * @throws UnprocessableEntityRequestException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function testPaymentCreation()
    {
        $profileId = 'pfl_QkEhN94Ba';
        $shopReference = 'test_reference_id';
        $payment = Payment::fromArray(array(
            'profileId' => $profileId,
            'amount' => array(
                'value' => '10.00',
                'currency' => 'EUR'
            ),
            'shippingAddress' => $this->getTestShippingAddressData(),
            'description' => "Order #{$shopReference}}",
            'method' => null,
            'metadata' => array(
                'order_id' => $shopReference
            ),
            'redirectUrl' => 'https://webshop.example.org/order/12345/',
            'webhookUrl' => 'https://webshop.example.org/payments/webhook/',
        ));
        $this->httpClient->setMockResponses(array($this->getMockPaymentCreate()));

        $createdPayment = $this->paymentService->createPayment($shopReference, $payment);

        $apiRequestHistory = $this->httpClient->getHistory();
        $this->assertCount(1, $apiRequestHistory);

        $expectedBody = $this->proxyTransformer->transformPayment($payment);
        $expectedBody['testmode'] = true;
        $requestBodyArray = json_decode($apiRequestHistory[0]['body'], true);
        $this->assertEquals('Authorization: Bearer test_token', $apiRequestHistory[0]['headers']['token']);
        $this->assertContains('/payments', $apiRequestHistory[0]['url']);
        $this->assertArrayNotHasKey('shippingAddress', $requestBodyArray);
        $this->assertEquals(json_encode($expectedBody), $apiRequestHistory[0]['body']);
        $this->assertNotNull($createdPayment);
        $this->assertEquals('https://www.mollie.com/payscreen/select-method/7UhSN1zuXS', $createdPayment->getLink('checkout')->getHref());
    }

    /**
     * @throws UnprocessableEntityRequestException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws QueryFilterInvalidParamException
     */
    public function testPaymentCreationAddsOrderReference()
    {
        $profileId = 'pfl_QkEhN94Ba';
        $shopReference = 'test_reference_id';
        $payment = Payment::fromArray(array(
            'profileId' => $profileId,
            'amount' => array(
                'value' => '10.00',
                'currency' => 'EUR'
            ),
            'description' => "Order #{$shopReference}}",
            'method' => null,
            'metadata' => array(
                'order_id' => $shopReference
            ),
            'redirectUrl' => 'https://webshop.example.org/order/12345/',
            'webhookUrl' => 'https://webshop.example.org/payments/webhook/',
        ));
        $this->httpClient->setMockResponses(array($this->getMockPaymentCreate()));

        $createdPayment = $this->paymentService->createPayment($shopReference, $payment);

        $queryFilter = new QueryFilter();
        $queryFilter->where('shopReference', Operators::EQUALS, $shopReference);
        /** @var OrderReference[] $savedOrderReferences */
        $savedOrderReferences = $this->orderReferenceRepository->select($queryFilter);
        $this->assertCount(1, $savedOrderReferences);
        $this->assertEquals($shopReference, $savedOrderReferences[0]->getShopReference());
        $this->assertEquals($createdPayment->getId(), $savedOrderReferences[0]->getMollieReference());
        $this->assertEquals(PaymentMethodConfig::API_METHOD_PAYMENT, $savedOrderReferences[0]->getApiMethod());
        $this->assertEquals($createdPayment->toArray(), $savedOrderReferences[0]->getPayload());
    }

    /**
     * @throws UnprocessableEntityRequestException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws QueryFilterInvalidParamException
     */
    public function testPaymentCreationUpdatesExistingOrderReference()
    {
        $profileId = 'pfl_QkEhN94Ba';
        $shopReference = 'test_reference_id';
        $payment = Payment::fromArray(array(
            'profileId' => $profileId,
            'amount' => array(
                'value' => '10.00',
                'currency' => 'EUR'
            ),
            'description' => "Order #{$shopReference}}",
            'method' => null,
            'metadata' => array(
                'order_id' => $shopReference
            ),
            'redirectUrl' => 'https://webshop.example.org/order/12345/',
            'webhookUrl' => 'https://webshop.example.org/payments/webhook/',
        ));
        $this->httpClient->setMockResponses(array($this->getMockPaymentCreate(), $this->getMockPaymentCreate()));
        $this->paymentService->createPayment($shopReference, $payment);

        $createdPayment = $this->paymentService->createPayment($shopReference, $payment);

        $queryFilter = new QueryFilter();
        $queryFilter->where('shopReference', Operators::EQUALS, $shopReference);
        /** @var OrderReference[] $savedOrderReferences */
        $savedOrderReferences = $this->orderReferenceRepository->select($queryFilter);
        $this->assertCount(1, $savedOrderReferences);
        $this->assertEquals($shopReference, $savedOrderReferences[0]->getShopReference());
        $this->assertEquals($createdPayment->getId(), $savedOrderReferences[0]->getMollieReference());
        $this->assertEquals(PaymentMethodConfig::API_METHOD_PAYMENT, $savedOrderReferences[0]->getApiMethod());
        $this->assertEquals($createdPayment->toArray(), $savedOrderReferences[0]->getPayload());
    }

    /**
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     */
    public function testPaymentCreationSendsShippingAddressForPayPalPaymentMethod()
    {
        $profileId = 'pfl_QkEhN94Ba';
        $shopReference = 'test_reference_id';
        $shippingAddress = $this->getTestShippingAddressData();
        $payment = Payment::fromArray(array(
            'profileId' => $profileId,
            'amount' => array(
                'value' => '10.00',
                'currency' => 'EUR'
            ),
            'shippingAddress' => $shippingAddress,
            'description' => "Order #{$shopReference}}",
            'method' => PaymentMethods::PayPal,
            'metadata' => array(
                'order_id' => $shopReference
            ),
            'redirectUrl' => 'https://webshop.example.org/order/12345/',
            'webhookUrl' => 'https://webshop.example.org/payments/webhook/',
        ));
        $this->httpClient->setMockResponses(array($this->getMockPaymentCreate()));

        $createdPayment = $this->paymentService->createPayment($shopReference, $payment);

        $apiRequestHistory = $this->httpClient->getHistory();
        $this->assertCount(1, $apiRequestHistory);

        $requestBody = json_decode($apiRequestHistory[0]['body'], true);
        $this->assertArrayHasKey('shippingAddress', $requestBody);
        $this->assertEquals($shippingAddress, $requestBody['shippingAddress']);
        $this->assertNotNull($createdPayment);
    }

    /**
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     */
    public function testPaymentCreationWithMultiplePaymentMethods()
    {
        $profileId = 'pfl_QkEhN94Ba';
        $shopReference = 'test_reference_id';
        $paymentMethods = array(PaymentMethods::PayPal, PaymentMethods::KlarnaSliceIt, PaymentMethods::KlarnaPayLater);
        $payment = Payment::fromArray(array(
            'profileId' => $profileId,
            'amount' => array(
                'value' => '10.00',
                'currency' => 'EUR'
            ),
            'description' => "Order #{$shopReference}}",
            'method' => $paymentMethods,
            'metadata' => array(
                'order_id' => $shopReference
            ),
            'redirectUrl' => 'https://webshop.example.org/order/12345/',
            'webhookUrl' => 'https://webshop.example.org/payments/webhook/',
        ));
        $this->httpClient->setMockResponses(array($this->getMockPaymentCreate()));

        $createdPayment = $this->paymentService->createPayment($shopReference, $payment);

        $apiRequestHistory = $this->httpClient->getHistory();
        $this->assertCount(1, $apiRequestHistory);

        $requestBody = json_decode($apiRequestHistory[0]['body'], true);
        $this->assertArrayHasKey('method', $requestBody);
        $this->assertEquals($paymentMethods, $requestBody['method']);
        $this->assertNotNull($createdPayment);
    }

    /**
     * @return array
     */
    protected function getTestShippingAddressData()
    {
        return array(
            'streetAndNumber' => 'Keizersgracht 313',
            'streetAdditional' => '4th floor',
            'city' => 'Haarlem',
            'region' => 'Noord-Holland',
            'postalCode' => '5678AB',
            'country' => 'NL',
        );
    }

    protected function getMockPaymentCreate()
    {
        $response = file_get_contents(__DIR__ . '/../Common/ApiResponses/paymentCreate.json');
        return new HttpResponse(200, array(), $response);
    }
}
