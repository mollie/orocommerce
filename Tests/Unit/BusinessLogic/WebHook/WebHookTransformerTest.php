<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\WebHook;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Order;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Payment;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\OrgToken\ProxyDataProvider;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Proxy;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Model\OrderReference;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\OrderReferenceService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\OrderService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Payments\PaymentService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\WebHook\OrderChangedWebHookEvent;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\WebHook\PaymentChangedWebHookEvent;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\WebHook\WebHookContext;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\WebHook\WebHookTransformer;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpClient;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpResponse;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\RepositoryRegistry;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Utility\Events\EventBus;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\BaseTestWithServices;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\ORM\MemoryRepository;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\TestHttpClient;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestServiceRegister;

class WebHookTransformerTest extends BaseTestWithServices
{
    /**
     * @var TestHttpClient
     */
    public $httpClient;
    /**
     * @var WebHookTransformer
     */
    private $webHookTransformer;
    /**
     * @var string
     */
    private $paymentId = 'tr_7UhSN1zuXS';
    /**
     * @var string
     */
    private $orderId = 'ord_pbjz8x';
    /**
     * @var string
     */
    private $paymentShopReference = '12345';
    /**
     * @var string
     */
    private $orderShopReference = 'test_reference_id';

    public function setUp()
    {
        parent::setUp();

        $this->webHookTransformer = WebHookTransformer::getInstance();

        RepositoryRegistry::registerRepository(OrderReference::CLASS_NAME, MemoryRepository::getClassName());

        $me = $this;

        $this->eventHistory = array();
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
            OrderReferenceService::CLASS_NAME,
            function () {
                return OrderReferenceService::getInstance();
            }
        );
        TestServiceRegister::registerService(
            PaymentService::CLASS_NAME,
            function () {
                return PaymentService::getInstance();
            }
        );
        TestServiceRegister::registerService(
            OrderService::CLASS_NAME,
            function () {
                return OrderService::getInstance();
            }
        );

        $this->setUpEventHandlers();
        $this->setUpTestOrderReferences();
    }

    public function tearDown()
    {
        WebHookTransformer::resetInstance();
        OrderReferenceService::resetInstance();
        PaymentService::resetInstance();
        OrderService::resetInstance();

        parent::tearDown();
    }

    /**
     * @throws HttpCommunicationException
     */
    public function testPaymentWebHookEventIsFiredForValidPaymentApiRequest()
    {
        $rawRequest = "id={$this->paymentId}";
        $this->httpClient->setMockResponses(array($this->getMockNotFoundResponse(), $this->getMockApiPaymentResponse()));

        $this->webHookTransformer->handle($rawRequest);

        // Asserts
        $this->assertCount(1, $this->eventHistory);

        /** @var PaymentChangedWebHookEvent $paymentEvent */
        $paymentEvent = $this->eventHistory[0];
        $this->assertEquals($this->getOrderReferencePaymentData(), $paymentEvent->getCurrentPayment());
        $this->assertEquals($this->getApiPaymentData(), $paymentEvent->getNewPayment());

        $expectedOrderReferencePayload = $paymentEvent->getNewPayment()->toArray();
        $actualOrderReference = $this->getStoredOrderReferenceData($paymentEvent->getNewPayment()->getId());
        $this->assertEquals(
            $expectedOrderReferencePayload,
            $actualOrderReference->getPayload(),
            'Successful web hook should update order reference.'
        );
    }

    /**
     * @throws HttpCommunicationException
     */
    public function testOrderWebHookEventIsFiredForValidOrderApiRequest()
    {
        $rawRequest = "id={$this->orderId}";
        $this->httpClient->setMockResponses(array($this->getMockNotFoundResponse(), $this->getMockApiOrderResponse()));

        $this->webHookTransformer->handle($rawRequest);

        // Asserts
        $this->assertCount(1, $this->eventHistory);

        /** @var OrderChangedWebHookEvent $orderEvent */
        $orderEvent = $this->eventHistory[0];
        $this->assertEquals($this->getOrderReferenceOrderData(), $orderEvent->getCurrentOrder());
        $this->assertEquals($this->getApiOrderData()->toArray(), $orderEvent->getNewOrder()->toArray());

        $expectedOrderReferencePayload = $orderEvent->getNewOrder()->toArray();
        $actualOrderReference = $this->getStoredOrderReferenceData($orderEvent->getNewOrder()->getId());
        $this->assertEquals(
            $expectedOrderReferencePayload,
            $actualOrderReference->getPayload(),
            'Successful web hook should update order reference.'
        );
    }

    /**
     * @throws HttpCommunicationException
     */
    public function testOrderWebHookEventIsFiredForPaymentOrderApiRequest()
    {
        $orderPaymentId = 'tr_ncaPcAhuUV';
        $rawRequest = "id={$orderPaymentId}";
        $this->httpClient->setMockResponses(array($this->getMockApiOrderPaymentResponse(), $this->getMockApiOrderResponse()));

        $this->webHookTransformer->handle($rawRequest);

        // Asserts
        $this->assertCount(1, $this->eventHistory);

        /** @var OrderChangedWebHookEvent $orderEvent */
        $orderEvent = $this->eventHistory[0];
        $this->assertEquals($this->getOrderReferenceOrderData(), $orderEvent->getCurrentOrder());
        $this->assertEquals($this->getApiOrderData()->toArray(), $orderEvent->getNewOrder()->toArray());

        $expectedOrderReferencePayload = $orderEvent->getNewOrder()->toArray();
        $actualOrderReference = $this->getStoredOrderReferenceData($orderEvent->getNewOrder()->getId());
        $this->assertEquals(
            $expectedOrderReferencePayload,
            $actualOrderReference->getPayload(),
            'Successful web hook should update order reference.'
        );
    }

    /**
     * @throws HttpCommunicationException
     */
    public function testNoWebHookIsFiredWenOrderReferenceDoesNotExist()
    {
        $rawRequest = 'id=not_existing_id';

        $this->webHookTransformer->handle($rawRequest);

        $this->assertCount(0, $this->eventHistory);
    }

    /**
     * @throws HttpCommunicationException
     */
    public function testNoWebHookIsFiredWenApiRequestFails()
    {
        $rawRequest = "id={$this->orderId}";
        $this->httpClient->setMockResponses(array($this->getMockNotFoundResponse(), $this->getMockNotFoundResponse()));

        $this->webHookTransformer->handle($rawRequest);

        $this->assertCount(0, $this->eventHistory);
    }

    public function testExceptionIsThrownWhenThereAreNetworkCommunicationProblems()
    {
        $rawRequest = "id={$this->orderId}";
        $thrownException = null;

        try {
            $this->webHookTransformer->handle($rawRequest);
        } catch (HttpCommunicationException $e) {
            $thrownException = $e;
        }

        $this->assertNotNull($thrownException);
        $this->assertCount(0, $this->eventHistory);
    }

    /**
     * @throws HttpCommunicationException
     */
    public function testWebHookContextStopsCallbackExecutionWhenStarted()
    {
        $rawRequest = "id={$this->paymentId}";
        $callbackIsInvoked = false;
        $this->httpClient->setMockResponses(array($this->getMockNotFoundResponse(), $this->getMockApiPaymentResponse()));

        /** @var EventBus $eventBuss */
        $eventBuss = ServiceRegister::getService(EventBus::CLASS_NAME);
        $eventBuss->when(
            PaymentChangedWebHookEvent::CLASS_NAME,
            WebHookContext::getProtectedCallable(
                function () use (&$callbackIsInvoked) {
                    $callbackIsInvoked = true;
                }
            )
        );

        $this->webHookTransformer->handle($rawRequest);

        $this->assertCount(1, $this->eventHistory);
        $this->assertFalse($callbackIsInvoked);
    }

    public function testWebHookContextDoesNotStopCallbackExecutionWhenNotStarted()
    {
        /** @var EventBus $eventBuss */
        $eventBuss = ServiceRegister::getService(EventBus::CLASS_NAME);
        $payment = $this->getOrderReferencePaymentData();
        $testEvent = new PaymentChangedWebHookEvent($this->getStoredOrderReferenceData($payment->getId()), $payment);
        $callbackIsInvoked = false;
        $callbackArgument = null;
        $eventBuss->when(
            PaymentChangedWebHookEvent::CLASS_NAME,
            WebHookContext::getProtectedCallable(
                function (PaymentChangedWebHookEvent $event) use (&$callbackIsInvoked, &$callbackArgument) {
                    $callbackIsInvoked = true;
                    $callbackArgument = $event;
                }
            )
        );

        $eventBuss->fire($testEvent);

        $this->assertCount(1, $this->eventHistory);
        $this->assertTrue($callbackIsInvoked);
        $this->assertSame($testEvent, $callbackArgument);
    }

    protected function setUpEventHandlers()
    {
        $me = $this;

        /** @var EventBus $eventBuss */
        $eventBuss = ServiceRegister::getService(EventBus::CLASS_NAME);
        $eventBuss->when(
            PaymentChangedWebHookEvent::CLASS_NAME,
            function (PaymentChangedWebHookEvent $event) use (&$me) {
                $me->eventHistory[] = $event;
            }
        );
        $eventBuss->when(
            OrderChangedWebHookEvent::CLASS_NAME,
            function (OrderChangedWebHookEvent $event) use (&$me) {
                $me->eventHistory[] = $event;
            }
        );
    }

    protected function setUpTestOrderReferences()
    {
        OrderReferenceService::getInstance()->updateOrderReference(
            $this->getOrderReferencePaymentData(),
            $this->paymentShopReference,
            PaymentMethodConfig::API_METHOD_PAYMENT
        );
        OrderReferenceService::getInstance()->updateOrderReference(
            $this->getOrderReferenceOrderData(),
            $this->orderShopReference,
            PaymentMethodConfig::API_METHOD_ORDERS
        );
    }

    /**
     * @param string $mollieReference Payment or order id
     *
     * @return OrderReference|null
     */
    protected function getStoredOrderReferenceData($mollieReference)
    {
        return OrderReferenceService::getInstance()->getByMollieReference($mollieReference);
    }

    /**
     * @return HttpResponse
     */
    protected function getMockNotFoundResponse()
    {
        return new HttpResponse(404, array(), '');
    }

    /**
     * @return HttpResponse
     */
    protected function getMockApiPaymentResponse()
    {
        $apiPaymentData = $this->getApiPaymentData();
        return new HttpResponse(200, array(), json_encode($apiPaymentData->toArray()));
    }

    /**
     * @return Payment
     */
    protected function getApiPaymentData()
    {
        $apiPaymentData = $this->getOrderReferencePaymentData();
        $apiPaymentData->setStatus('paid');

        return $apiPaymentData;
    }

    /**
     * @return Payment
     */
    protected function getOrderReferencePaymentData()
    {
        return Payment::fromArray(json_decode($this->getMockPaymentJson(), true));
    }

    /**
     * @return string
     */
    protected function getMockPaymentJson()
    {
        return file_get_contents(__DIR__ . '/../Common/ApiResponses/paymentCreate.json');
    }

    /**
     * @return HttpResponse
     */
    protected function getMockApiOrderResponse()
    {
        $apiOrderData = $this->getApiOrderData();
        return new HttpResponse(200, array(), json_encode($apiOrderData->toArray()));
    }

    /**
     * @return HttpResponse
     */
    protected function getMockApiOrderPaymentResponse()
    {
        $apiOrderData = $this->getApiOrderData();
        $embeds = $apiOrderData->getEmbedded();
        /** @var Payment[] $apiOrderPaymentData */
        $apiOrderPaymentData = $embeds['payments'];

        return new HttpResponse(200, array(), json_encode($apiOrderPaymentData[0]->toArray()));
    }

    /**
     * @return Order
     */
    protected function getApiOrderData()
    {
        $apiOrderData = $this->getOrderReferenceOrderData();
        $apiOrderData->setStatus('paid');

        return $apiOrderData;
    }

    /**
     * @return Order
     */
    protected function getOrderReferenceOrderData()
    {
        return Order::fromArray(json_decode($this->getMockOrderJson(), true));
    }

    /**
     * @return string
     */
    protected function getMockOrderJson()
    {
        return file_get_contents(__DIR__ . '/../Common/ApiResponses/orderResponse.json');
    }
}
