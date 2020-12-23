<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Refunds;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Amount;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Order;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\OrderLine;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Payment;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Refunds\Refund;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\OrgToken\ProxyDataProvider;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Proxy;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Exceptions\ReferenceNotFoundException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Model\OrderReference;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\OrderReferenceService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\OrderService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\ORM\Interfaces\RepositoryInterface;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Refunds\Exceptions\RefundNotAllowedException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Refunds\RefundService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpClient;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpResponse;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\RepositoryRegistry;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\BaseTestWithServices;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\ORM\MemoryRepository;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\TestHttpClient;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestServiceRegister;

class RefundServiceTest extends BaseTestWithServices
{
    /**
     * @var string
     */
    protected $orderShopReference = 'test_reference_id';
    /**
     * @var string
     */
    protected $paymentShopReference = '12345';

    /**
     * @var TestHttpClient
     */
    public $httpClient;
    /**
     * @var ProxyDataProvider
     */
    public $proxyTransformer;
    /**
     * @var RefundService
     */
    private $refundService;
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
            OrderService::CLASS_NAME,
            function () {
                return OrderService::getInstance();
            }
        );

        $this->shopConfig->setAuthorizationToken('test_token');
        $this->shopConfig->setTestMode(true);
        $this->refundService = RefundService::getInstance();
        $this->orderReferenceRepository = RepositoryRegistry::getRepository(OrderReference::CLASS_NAME);
        $this->setUpTestOrderReferences();
    }

    public function tearDown()
    {
        RefundService::resetInstance();
        OrderService::resetInstance();

        parent::tearDown();
    }

    /**
     * @throws UnprocessableEntityRequestException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws ReferenceNotFoundException
     */
    public function testRefundPayment()
    {
        $this->httpClient->setMockResponses(array($this->getMockOrderResponse('paymentRefund')));
        $refund = $this->createRefund(100);
        $createdRefund = $this->refundService->refundPayment($this->paymentShopReference, $refund);
        $expectedBody = $this->proxyTransformer->transformPaymentRefund($refund);
        $expectedBody['testmode'] = true;
        $apiRequestHistory = $this->httpClient->getHistory();

        $this->assertInstanceOf('Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Refunds\Refund', $createdRefund);
        $this->assertArrayNotHasKey('lines', $expectedBody);
        $this->assertEquals($expectedBody['description'], 'test description');
        $this->assertEquals($expectedBody['amount']['currency'], 'USD');
        $this->assertEquals($expectedBody['amount']['value'], 100);
        $this->assertCount(1, $apiRequestHistory);
        $this->assertEquals('Authorization: Bearer test_token', $apiRequestHistory[0]['headers']['token']);
        $this->assertContains('/payments/tr_7UhSN1zuXS/refunds', $apiRequestHistory[0]['url']);
        $this->assertEquals(json_encode($expectedBody), $apiRequestHistory[0]['body']);
    }

    /**
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws ReferenceNotFoundException
     * @throws UnprocessableEntityRequestException
     */
    public function testRefundPaymentWhenReferenceNotExist()
    {
        $this->httpClient->setMockResponses(array($this->getMockOrderResponse('paymentRefund')));
        $refund = $this->createRefund(100);
        $this->expectException('\Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Exceptions\ReferenceNotFoundException');
        $this->refundService->refundPayment('unknown_reference', $refund);
    }

    /**
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws ReferenceNotFoundException
     * @throws UnprocessableEntityRequestException
     */
    public function testRefundOrderLines()
    {
        $this->httpClient->setMockResponses(array(
            $this->getMockOrderResponse('orderResponse'),
            $this->getMockOrderResponse('orderLineRefund'),
            ));
        $refund = $this->createRefund(100, true);
        $createdRefund = $this->refundService->refundOrderLines($this->orderShopReference, $refund);
        $apiRequestHistory = $this->httpClient->getHistory();
        $expectedBody = json_decode($apiRequestHistory[1]['body'], true);
        $this->assertInstanceOf('Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Refunds\Refund', $createdRefund);
        $this->assertArrayHasKey('lines', $expectedBody);
        $this->assertCount(1, $expectedBody['lines']);
        $this->assertArrayHasKey('id', $expectedBody['lines'][0]);
        $this->assertArrayHasKey('quantity', $expectedBody['lines'][0]);
        $this->assertCount(2, $apiRequestHistory);
        $this->assertContains('orders/ord_pbjz8x/refunds', $apiRequestHistory[1]['url']);
        $this->assertEquals(json_encode($expectedBody), $apiRequestHistory[1]['body']);
    }

    /**
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws ReferenceNotFoundException
     * @throws UnprocessableEntityRequestException
     */
    public function testRefundAllLines()
    {
        $this->httpClient->setMockResponses(array(
            $this->getMockOrderResponse('orderResponse'),
            $this->getMockOrderResponse('orderLineRefund'),
            ));
        $lines = array();
        $refundLine1 = new OrderLine();
        $refundLine1->setId('odl_dgtxyl');
        $refundLine1->setQuantity(2);
        $lines[] = $refundLine1;

        $refundLine2 = new OrderLine();
        $refundLine2->setId('odl_jp31jz');
        $refundLine2->setQuantity(1);
        $lines[] = $refundLine2;

        $refund = new Refund();
        $refund->setLines($lines);
        $createdRefund = $this->refundService->refundOrderLines($this->orderShopReference, $refund);
        $apiRequestHistory = $this->httpClient->getHistory();
        $this->assertInstanceOf('Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Refunds\Refund', $createdRefund);

        $this->assertCount(2, $apiRequestHistory);
        $this->assertContains('orders/ord_pbjz8x/refunds', $apiRequestHistory[1]['url']);
        $body = json_decode($apiRequestHistory[1]['body'], true);
        $this->assertCount(0, $body['lines']);
    }

    /**
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws ReferenceNotFoundException
     * @throws UnprocessableEntityRequestException
     * @throws RefundNotAllowedException
     */
    public function testRefundWholeOrderWhenRefundIsPossible()
    {
        $this->httpClient->setMockResponses(array(
            $this->getMockOrderResponse('orderResponse'),
            $this->getMockOrderResponse('paymentRefund'),
            $this->getMockOrderResponse('paymentRefund'),
        ));

        $refund = $this->createRefund(900);
        $this->refundService->refundWholeOrder($this->orderShopReference, $refund);
        $apiRequestHistory = $this->httpClient->getHistory();
        $this->assertCount(3, $apiRequestHistory);
        $this->assertEquals(Proxy::HTTP_METHOD_GET, $apiRequestHistory[0]['method']);
        $this->assertContains('orders', $apiRequestHistory[0]['url']);
        $this->assertEmpty($apiRequestHistory[0]['body']);

        $this->assertEquals(Proxy::HTTP_METHOD_POST, $apiRequestHistory[1]['method']);
        $this->assertContains('payments/tr_fksdjfsdk/refunds', $apiRequestHistory[1]['url']);
        $bodyDecoded = json_decode($apiRequestHistory[1]['body'], true);
        $this->assertEquals(400, $bodyDecoded['amount']['value']);

        $this->assertEquals(Proxy::HTTP_METHOD_POST, $apiRequestHistory[2]['method']);
        $bodyDecoded = json_decode($apiRequestHistory[2]['body'], true);
        $this->assertEquals(500, $bodyDecoded['amount']['value']);
        $this->assertContains('payments/tr_fksdjfsdkFF/refunds', $apiRequestHistory[2]['url']);
    }

    /**
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws ReferenceNotFoundException
     * @throws UnprocessableEntityRequestException
     * @throws RefundNotAllowedException
     */
    public function testRefundWholeOrderWhenRefundIsNotPossible()
    {
        $this->httpClient->setMockResponses(array(
            $this->getMockOrderResponse('orderResponse'),
            $this->getMockOrderResponse('paymentRefund'),
            $this->getMockOrderResponse('paymentRefund'),
        ));

        $refund = $this->createRefund(1500);
        $this->expectException('\Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Refunds\Exceptions\RefundNotAllowedException');
        $this->refundService->refundWholeOrder($this->orderShopReference, $refund);
        $apiRequestHistory = $this->httpClient->getHistory();
        $this->assertCount(1, $apiRequestHistory);
        $this->assertEquals(Proxy::HTTP_METHOD_GET, $apiRequestHistory[0]['method']);
        $this->assertContains('orders', $apiRequestHistory[0]['url']);
        $this->assertEmpty($apiRequestHistory[0]['body']);
    }

    /**
     * @param $jsonFile
     *
     * @return HttpResponse
     */
    protected function getMockOrderResponse($jsonFile)
    {
        $response = file_get_contents(__DIR__ . "/../Common/ApiResponses/{$jsonFile}.json");

        return new HttpResponse(200, array(), $response);
    }

    /**
     * @param $amountValue
     * @param bool $addLines
     *
     * @return Refund
     */
    protected function createRefund($amountValue, $addLines = false)
    {
        $amount = new Amount();
        $amount->setCurrency('USD');
        $amount->setAmountValue($amountValue);
        $refund = new Refund();
        $refund->setAmount($amount);
        $refund->setDescription('test description');

        if ($addLines) {
            $lines = array();
            $line = new OrderLine();
            $line->setQuantity(2);
            $line->setId('odl_dgtxyl');

            $lines[] = $line;

            $refund->setLines($lines);
        }

        return $refund;
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
     * @param string $shopReference Payment or order id
     *
     * @return OrderReference|null
     */
    protected function getStoredOrderReferenceData($shopReference)
    {
        return OrderReferenceService::getInstance()->getByShopReference($shopReference);
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
    protected function getMockOrderJson()
    {
        return file_get_contents(__DIR__ . '/../Common/ApiResponses/orderResponse.json');
    }

    /**
     * @return string
     */
    protected function getMockPaymentJson()
    {
        return file_get_contents(__DIR__ . '/../Common/ApiResponses/paymentCreate.json');
    }


    /**
     * @return Order
     */
    protected function getOrderReferenceOrderData()
    {
        return Order::fromArray(json_decode($this->getMockOrderJson(), true));
    }
}
