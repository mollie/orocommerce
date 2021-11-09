<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Orders;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\ApiKey\ApiKeyAuthService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\Interfaces\AuthorizationService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Order;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\OrgToken\ProxyDataProvider;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Proxy;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Exceptions\ReferenceNotFoundException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Model\OrderReference;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\OrderReferenceService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\OrderService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\ORM\Interfaces\RepositoryInterface;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\PaymentMethods;
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

class OrderServiceTest extends BaseTestWithServices
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
     * @var OrderService
     */
    private $orderService;
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
            OrderReferenceService::CLASS_NAME,
            function () {
                return OrderReferenceService::getInstance();
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
        $this->orderService = OrderService::getInstance();
        $this->orderReferenceRepository = RepositoryRegistry::getRepository(OrderReference::CLASS_NAME);
    }

    public function tearDown()
    {
        OrderService::resetInstance();

        parent::tearDown();
    }

    /**
     * @throws UnprocessableEntityRequestException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function testOrderCreation()
    {
        $shopReference = 'test_reference_id';
        $profileId = 'pfl_URR55HPMGx';
        $order = $this->getOrderData($shopReference, $profileId);
        $this->httpClient->setMockResponses(array($this->getMockOrderResponse(), $this->getMockOrderResponse()));

        $createdOrder = $this->orderService->createOrder($shopReference, $order);

        $apiRequestHistory = $this->httpClient->getHistory();
        $expectedBody = $this->proxyTransformer->transformOrder($order);
        $expectedBody['testmode'] = true;
        $this->assertCount(2, $apiRequestHistory);
        $this->assertEquals('Authorization: Bearer test_token', $apiRequestHistory[0]['headers']['token']);
        $this->assertContains('/orders', $apiRequestHistory[0]['url']);
        $this->assertEquals(json_encode($expectedBody), $apiRequestHistory[0]['body']);
        $this->assertNotNull($createdOrder);
        $this->assertEquals('https://www.mollie.com/payscreen/order/checkout/pbjz8x', $createdOrder->getLink('checkout')->getHref());
    }

    /**
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     */
    public function testOrderCreationWithMultiplePaymentMethods()
    {
        $shopReference = 'test_reference_id';
        $profileId = 'pfl_URR55HPMGx';
        $paymentMethods = array(
            PaymentMethods::PayPal,
            PaymentMethods::KlarnaSliceIt,
            PaymentMethods::KlarnaPayLater,
            PaymentMethods::KlarnaPayNow,
        );
        $order = $this->getOrderData($shopReference, $profileId);
        $order->setMethods($paymentMethods);
        $this->httpClient->setMockResponses(array($this->getMockOrderResponse(), $this->getMockOrderResponse()));

        $createdOrder = $this->orderService->createOrder($shopReference, $order);

        $apiRequestHistory = $this->httpClient->getHistory();
        $this->assertCount(2, $apiRequestHistory);

        $createRequestBody = json_decode($apiRequestHistory[0]['body'], true);
        $this->assertArrayHasKey('method', $createRequestBody);
        $this->assertEquals($paymentMethods, $createRequestBody['method']);
        $this->assertNotNull($createdOrder);
    }

    /**
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     * @throws QueryFilterInvalidParamException
     */
    public function testOrderCreationAddsOrderReference()
    {
        $shopReference = 'test_reference_id';
        $profileId = 'pfl_URR55HPMGx';
        $order = $this->getOrderData($shopReference, $profileId);
        $this->httpClient->setMockResponses(array($this->getMockOrderResponse(), $this->getMockOrderResponse()));

        $createdOrder = $this->orderService->createOrder($shopReference, $order);
        $createdOrder->setStatus(null);

        $queryFilter = new QueryFilter();
        $queryFilter->where('shopReference', Operators::EQUALS, $shopReference);
        /** @var OrderReference[] $savedOrderReferences */
        $savedOrderReferences = $this->orderReferenceRepository->select($queryFilter);
        $this->assertCount(1, $savedOrderReferences);
        $this->assertEquals($shopReference, $savedOrderReferences[0]->getShopReference());
        $this->assertEquals($createdOrder->getId(), $savedOrderReferences[0]->getMollieReference());
        $this->assertEquals(PaymentMethodConfig::API_METHOD_ORDERS, $savedOrderReferences[0]->getApiMethod());
        $this->assertEquals($createdOrder->toArray(), $savedOrderReferences[0]->getPayload());
    }

    /**
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     */
    public function testPositiveAdjustmentOfOrderTotal()
    {
        $shopReference = 'test_reference_id';
        $profileId = 'pfl_URR55HPMGx';
        $adjustmentAmount = 1.3;
        $order = $this->getOrderData($shopReference, $profileId);
        $this->httpClient->setMockResponses(array($this->getMockOrderResponse(), $this->getMockOrderResponse()));
        $order->getAmount()->setAmountValue((float)$order->getAmount()->getAmountValue() + $adjustmentAmount);

        $this->orderService->createOrder($shopReference, $order);

        $apiRequestHistory = $this->httpClient->getHistory();
        $this->assertCount(2, $apiRequestHistory);
        $requestBody = json_decode($apiRequestHistory[0]['body'], true);
        $this->assertCount(3, $requestBody['lines']);
        $this->assertEquals($adjustmentAmount, $requestBody['lines'][2]['totalAmount']['value']);
        $this->assertEquals($order->getAmount()->getCurrency(), $requestBody['lines'][2]['totalAmount']['currency']);
        $this->assertEquals('surcharge', $requestBody['lines'][2]['type']);
        $this->assertEquals(0, (float)$requestBody['lines'][2]['vatRate']);
    }

    /**
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     */
    public function testNegativeAdjustmentOfOrderTotal()
    {
        $shopReference = 'test_reference_id';
        $profileId = 'pfl_URR55HPMGx';
        $adjustmentAmount = -1.3;
        $order = $this->getOrderData($shopReference, $profileId);
        $this->httpClient->setMockResponses(array($this->getMockOrderResponse(), $this->getMockOrderResponse()));
        $order->getAmount()->setAmountValue((float)$order->getAmount()->getAmountValue() + $adjustmentAmount);

        $this->orderService->createOrder($shopReference, $order);

        $apiRequestHistory = $this->httpClient->getHistory();
        $this->assertCount(2, $apiRequestHistory);
        $requestBody = json_decode($apiRequestHistory[0]['body'], true);
        $this->assertCount(3, $requestBody['lines']);
        $this->assertEquals($adjustmentAmount, $requestBody['lines'][2]['totalAmount']['value']);
        $this->assertEquals($order->getAmount()->getCurrency(), $requestBody['lines'][2]['totalAmount']['currency']);
        $this->assertEquals('discount', $requestBody['lines'][2]['type']);
        $this->assertEquals(0, (float)$requestBody['lines'][2]['vatRate']);
    }

    /**
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     */
    public function testMinimalDetectableAdjustmentOfOrderTotal()
    {
        $shopReference = 'test_reference_id';
        $profileId = 'pfl_URR55HPMGx';
        $adjustmentAmount = 0.01;
        $order = $this->getOrderData($shopReference, $profileId);
        $this->httpClient->setMockResponses(array($this->getMockOrderResponse(), $this->getMockOrderResponse()));
        $order->getAmount()->setAmountValue((float)$order->getAmount()->getAmountValue() + $adjustmentAmount);

        $this->orderService->createOrder($shopReference, $order);

        $apiRequestHistory = $this->httpClient->getHistory();
        $this->assertCount(2, $apiRequestHistory);
        $requestBody = json_decode($apiRequestHistory[0]['body'], true);
        $this->assertCount(3, $requestBody['lines']);
        $this->assertEquals($adjustmentAmount, $requestBody['lines'][2]['totalAmount']['value']);
        $this->assertEquals($order->getAmount()->getCurrency(), $requestBody['lines'][2]['totalAmount']['currency']);
        $this->assertEquals('surcharge', $requestBody['lines'][2]['type']);
        $this->assertEquals(0, (float)$requestBody['lines'][2]['vatRate']);
    }

    /**
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     * @throws QueryFilterInvalidParamException
     */
    public function testOrderCreationUpdatesExistingOrderReference()
    {
        $shopReference = 'test_reference_id';
        $profileId = 'pfl_URR55HPMGx';
        $order = $this->getOrderData($shopReference, $profileId);
        $this->httpClient->setMockResponses(array($this->getMockOrderResponse(), $this->getMockOrderResponse()));
        $this->orderService->createOrder($shopReference, $order);
        $this->httpClient->setMockResponses(array($this->getMockOrderResponse(), $this->getMockOrderResponse()));

        $createdOrder = $this->orderService->createOrder($shopReference, $order);

        $queryFilter = new QueryFilter();
        $queryFilter->where('shopReference', Operators::EQUALS, $shopReference);
        /** @var OrderReference[] $savedOrderReferences */
        $savedOrderReferences = $this->orderReferenceRepository->select($queryFilter);
        $this->assertCount(1, $savedOrderReferences);
        $this->assertEquals($shopReference, $savedOrderReferences[0]->getShopReference());
        $this->assertEquals($createdOrder->getId(), $savedOrderReferences[0]->getMollieReference());
        $this->assertEquals(PaymentMethodConfig::API_METHOD_ORDERS, $savedOrderReferences[0]->getApiMethod());
        $createdOrder->setStatus(null);
        $this->assertEquals($createdOrder->toArray(), $savedOrderReferences[0]->getPayload());
    }

    public function testCreationRemovesInvalidPhoneNumbers()
    {
        $shopReference = 'test_reference_id';
        $profileId = 'pfl_URR55HPMGx';
        $invalidPhone = '123-AA';
        $order = $this->getOrderData($shopReference, $profileId);
        $this->httpClient->setMockResponses(array($this->getMockOrderResponse(), $this->getMockOrderResponse()));
        $order->getShippingAddress()->setPhone($invalidPhone);

        $this->orderService->createOrder($shopReference, $order);

        $apiRequestHistory = $this->httpClient->getHistory();
        $this->assertCount(2, $apiRequestHistory);
        $requestBody = json_decode($apiRequestHistory[0]['body'], true);
        $this->assertNotEmpty($requestBody['shippingAddress']);
        $this->assertArrayNotHasKey('phone', $requestBody['shippingAddress']);
        $this->assertNotEmpty($requestBody['billingAddress']);
        $this->assertArrayHasKey('phone', $requestBody['billingAddress']);
    }

    public function testCreationSanitizePhoneNumbers()
    {
        $shopReference = 'test_reference_id';
        $profileId = 'pfl_URR55HPMGx';
        $commonDelimitedPhoneEntry = '123 45/ 67 - 89 12-34';
        $order = $this->getOrderData($shopReference, $profileId);
        $this->httpClient->setMockResponses(array($this->getMockOrderResponse(), $this->getMockOrderResponse()));
        $order->getShippingAddress()->setPhone($commonDelimitedPhoneEntry);

        $this->orderService->createOrder($shopReference, $order);

        $apiRequestHistory = $this->httpClient->getHistory();
        $this->assertCount(2, $apiRequestHistory);
        $requestBody = json_decode($apiRequestHistory[0]['body'], true);
        $this->assertNotEmpty($requestBody['shippingAddress']);
        $this->assertArrayHasKey('phone', $requestBody['shippingAddress']);
        $this->assertSame('+1234567891234', $requestBody['shippingAddress']['phone']);
    }

    public function testCreationRemovesLeadingZerosFromPhoneNumbers()
    {
        $shopReference = 'test_reference_id';
        $profileId = 'pfl_URR55HPMGx';
        $phoneNumberWithLeadingZeros = '00123456789';
        $order = $this->getOrderData($shopReference, $profileId);
        $this->httpClient->setMockResponses(array($this->getMockOrderResponse(), $this->getMockOrderResponse()));
        $order->getShippingAddress()->setPhone($phoneNumberWithLeadingZeros);

        $this->orderService->createOrder($shopReference, $order);

        $apiRequestHistory = $this->httpClient->getHistory();
        $this->assertCount(2, $apiRequestHistory);
        $requestBody = json_decode($apiRequestHistory[0]['body'], true);
        $this->assertNotEmpty($requestBody['shippingAddress']);
        $this->assertArrayHasKey('phone', $requestBody['shippingAddress']);
        $this->assertSame('+123456789', $requestBody['shippingAddress']['phone']);
    }

    /**
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     * @throws ReferenceNotFoundException
     */
    public function testGetOrderWhenReferenceNotFound()
    {
        $shopReference = 'unknown_reference';
        $this->httpClient->setMockResponses(array($this->getMockOrderResponse()));

        $this->expectException('\Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Exceptions\ReferenceNotFoundException');
        $this->orderService->getOrder($shopReference);
    }

    /**
     * @param string $shopReference
     * @param string $profileId
     *
     * @return Order
     */
    protected function getOrderData($shopReference, $profileId)
    {
        return Order::fromArray(array(
            'profileId' => $profileId,
            'amount' => array(
                'value' => '1027.99',
                'currency' => 'EUR'
            ),
            'billingAddress' => array(
                'organizationName' => 'Mollie B.V.',
                'streetAndNumber' => 'Keizersgracht 313',
                'city' => 'Amsterdam',
                'region' => 'Noord-Holland',
                'postalCode' => '1234AB',
                'country' => 'NL',
                'title' => 'Dhr.',
                'givenName' => 'Piet',
                'familyName' => 'Mondriaan',
                'email' => 'test@example.com',
                'phone' => '+31309202070',
            ),
            'shippingAddress' => array(
                'organizationName' => 'Mollie B.V.',
                'streetAndNumber' => 'Keizersgracht 313',
                'streetAdditional' => '4th floor',
                'city' => 'Haarlem',
                'region' => 'Noord-Holland',
                'postalCode' => '5678AB',
                'country' => 'NL',
                'title' => 'Mr.',
                'givenName' => 'Chuck',
                'familyName' => 'Norris',
                'email' => 'test@example.net',
            ),
            'metadata' => array(
                'order_id' => $shopReference,
            ),
            'consumerDateOfBirth' => '1958-01-31',
            'locale' => 'nl_NL',
            'orderNumber' => $shopReference,
            'redirectUrl' => 'https://webshop.example.org/order/12345/',
            'webhookUrl' => 'https://webshop.example.org/order/webhook/',
            'method' => 'klarnapaylater',
            'lines' => array(
                array(
                    'type' => 'physical',
                    'sku' => '5702016116977',
                    'name' => 'LEGO 42083 Bugatti Chiron',
                    'productUrl' => 'https://shop.lego.com/nl-NL/Bugatti-Chiron-42083',
                    'imageUrl' => 'https://sh-s7-live-s.legocdn.com/is/image//LEGO/42083_alt1?$main$',
                    'metadata' => array(
                        'order_id' => $shopReference,
                    ),
                    'quantity' => 2,
                    'vatRate' => '21.00',
                    'unitPrice' => array(
                        'currency' => 'EUR',
                        'value' => '399.00'
                    ),
                    'totalAmount' => array(
                        'currency' => 'EUR',
                        'value' => '698.00'
                    ),
                    'discountAmount' => array(
                        'currency' => 'EUR',
                        'value' => '100.00'
                    ),
                    'vatAmount' => array(
                        'currency' => 'EUR',
                        'value' => '121.14'
                    )
                ),
                array(
                    'type' => 'physical',
                    'sku' => '5702015594028',
                    'name' => 'LEGO 42056 Porsche 911 GT3 RS',
                    'productUrl' => 'https://shop.lego.com/nl-NL/Porsche-911-GT3-RS-42056',
                    'imageUrl' => 'https://sh-s7-live-s.legocdn.com/is/image/LEGO/42056?$PDPDefault$',
                    'quantity' => 1,
                    'vatRate' => '21.00',
                    'unitPrice' => array(
                        'currency' => 'EUR',
                        'value' => '329.99'
                    ),
                    'totalAmount' => array(
                        'currency' => 'EUR',
                        'value' => '329.99'
                    ),
                    'vatAmount' => array(
                        'currency' => 'EUR',
                        'value' => '57.27'
                    )
                )
            )
        ));
    }

    protected function getMockOrderResponse()
    {
        $response = file_get_contents(__DIR__ . '/../Common/ApiResponses/orderResponse.json');

        return new HttpResponse(200, array(), $response);
    }
}
