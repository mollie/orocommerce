<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\WebHook\Handler;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Order;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Interfaces\OrderLineTransitionService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Interfaces\OrderTransitionService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Model\OrderReference;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\OrderReferenceService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\WebHookHandler\StatusWebHookHandler;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\WebHook\OrderChangedWebHookEvent;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\RepositoryRegistry;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\BaseTestWithServices;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\ORM\MemoryRepository;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\TestOrderLineTransitionService;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\TestOrderTransitionService;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestServiceRegister;

class OrderStatusWebHookHandlerTest extends BaseTestWithServices
{
    /**
     * @var TestOrderTransitionService
     */
    public $orderTransitionService;
    /**
     * @var TestOrderLineTransitionService
     */
    public $orderLineTransitionService;
    /**
     * @var StatusWebHookHandler
     */
    private $handler;
    /**
     * @var string
     */
    private $shopReference = 'test_reference_id';

    public function setUp()
    {
        parent::setUp();

        $me = $this;

        $this->handler = new StatusWebHookHandler();
        $this->orderTransitionService = new TestOrderTransitionService();
        $this->orderLineTransitionService = new TestOrderLineTransitionService();

        RepositoryRegistry::registerRepository(OrderReference::CLASS_NAME, MemoryRepository::getClassName());

        TestServiceRegister::registerService(
            OrderTransitionService::CLASS_NAME,
            function () use ($me) {
                return $me->orderTransitionService;
            }
        );
        TestServiceRegister::registerService(
            OrderLineTransitionService::CLASS_NAME,
            function () use ($me) {
                return $me->orderLineTransitionService;
            }
        );

        $this->setUpTestOrderReferences();
    }

    /**
     * @param string $status
     * @param string $expectedMethodCall
     *
     * @dataProvider orderStatusProvider
     */
    public function testOrderStatusChangeDetection($status, $expectedMethodCall)
    {
        $newOrder = $this->getOrderReferenceData();
        $newOrder->setStatus($status);
        $event = new OrderChangedWebHookEvent($this->getOrderReference(), $newOrder);

        $this->handler->handle($event);

        $callHistory = $this->orderTransitionService->getCallHistory($expectedMethodCall);
        $this->assertCount(
            1,
            $callHistory,
            "Order status change to '{$status}' should trigger OrderTransitionService::{$expectedMethodCall}() method."
        );
        $this->assertEquals($this->shopReference, $callHistory[0]['orderId']);
        $this->assertEquals($newOrder->getMetadata(), $callHistory[0]['metadata']);
    }

    /**
     * @param string $status
     * @param string $expectedMethodCall
     *
     * @dataProvider orderPaymentStatusProvider
     */
    public function testOrderPaymentStatusChangeDetection($status, $expectedMethodCall)
    {
        $newOrder = $this->getOrderReferenceData();
        $embeds = $newOrder->getEmbedded();
        $embeds['payments'][0]->setStatus($status);
        $newOrder->setEmbedded($embeds);
        $event = new OrderChangedWebHookEvent($this->getOrderReference(), $newOrder);

        $this->handler->handle($event);

        $callHistory = $this->orderTransitionService->getCallHistory($expectedMethodCall);
        $this->assertCount(
            1,
            $callHistory,
            "Order payment status change to '{$status}' should trigger OrderTransitionService::{$expectedMethodCall}() method."
        );
        $this->assertEquals($this->shopReference, $callHistory[0]['orderId']);
        $this->assertEquals($newOrder->getMetadata(), $callHistory[0]['metadata']);
    }

    /**
     * @param string $orderStatus
     * @param string $orderLineStatus
     * @param string $expectedOrderMethodCall
     * @param string $expectedOrderLineMethodCall
     *
     * @dataProvider orderLineStatusProvider
     */
    public function testOrderLineNormalizationDetection(
        $orderStatus,
        $orderLineStatus,
        $expectedOrderMethodCall,
        $expectedOrderLineMethodCall
    ) {
        $newOrder = $this->getOrderReferenceData();
        $newOrder->setStatus($orderStatus);
        $newOrderLines = $newOrder->getLines();
        $newOrderLines[0]->setStatus($orderLineStatus);

        $event = new OrderChangedWebHookEvent($this->getOrderReference(), $newOrder);

        $this->handler->handle($event);

        $callHistory = $this->orderTransitionService->getCallHistory();
        $this->assertCount(1, $callHistory);
        $this->assertArrayHasKey(
            $expectedOrderMethodCall,
            $callHistory,
            "Order status change to '{$orderStatus}' should trigger OrderTransitionService::{$expectedOrderMethodCall}() method."
        );
        $callHistory = $this->orderLineTransitionService->getCallHistory();
        $this->assertCount(1, $callHistory);
        $this->assertArrayHasKey(
            $expectedOrderLineMethodCall,
            $callHistory,
            "Order status change should normalize order lines by calling OrderTransitionService::{$expectedOrderLineMethodCall}() method."
        );
        $this->assertCount(1, $callHistory[$expectedOrderLineMethodCall]);
    }

    public function testUnknownStatus()
    {
        $newOrder = $this->getOrderReferenceData();
        $newOrder->setStatus('test_unknown_status');
        $event = new OrderChangedWebHookEvent($this->getOrderReference(), $newOrder);

        $this->handler->handle($event);

        $callHistory = $this->orderTransitionService->getCallHistory();
        $this->assertCount(0, $callHistory);
    }

    public function testUnchangedStatus()
    {
        $newOrder = $this->getOrderReferenceData();
        $newOrder->setStatus('paid');
        $event = new OrderChangedWebHookEvent($this->getOrderReference(), $newOrder);
        $event->getCurrentOrder()->setStatus($newOrder->getStatus());

        $this->handler->handle($event);

        $callHistory = $this->orderTransitionService->getCallHistory();
        $this->assertCount(0, $callHistory);
    }

    public function testUnknownOrderLineStatus()
    {
        $newOrder = $this->getOrderReferenceData();
        $newOrder->setStatus('paid');
        $newOrderLines = $newOrder->getLines();
        $newOrderLines[0]->setStatus('test_unknown_status');
        $event = new OrderChangedWebHookEvent($this->getOrderReference(), $newOrder);

        $this->handler->handle($event);

        $callHistory = $this->orderLineTransitionService->getCallHistory();
        $this->assertCount(0, $callHistory);
    }

    public function orderStatusProvider()
    {
        return array(
            array(
                'status' => 'paid',
                'expectedMethodCall' => 'payOrder',
            ),
            array(
                'status' => 'expired',
                'expectedMethodCall' => 'expireOrder',
            ),
            array(
                'status' => 'canceled',
                'expectedMethodCall' => 'cancelOrder',
            ),
            array(
                'status' => 'authorized',
                'expectedMethodCall' => 'authorizeOrder',
            ),
            array(
                'status' => 'completed',
                'expectedMethodCall' => 'completeOrder',
            ),
        );
    }

    public function orderPaymentStatusProvider()
    {
        return array(
            array(
                'status' => 'expired',
                'expectedMethodCall' => 'expireOrder',
            ),
            array(
                'status' => 'canceled',
                'expectedMethodCall' => 'cancelOrder',
            ),
            array(
                'status' => 'failed',
                'expectedMethodCall' => 'failOrder',
            ),
        );
    }

    public function orderLineStatusProvider()
    {
        return array(
            array(
                'orderStatus' => 'paid',
                'orderLineStatus' => 'paid',
                'expectedOrderMethodCall' => 'payOrder',
                'expectedOrderLineMethodCall' => 'payOrderLine',
            ),
            array(
                'orderStatus' => 'expired',
                'orderLineStatus' => 'canceled',
                'expectedOrderMethodCall' => 'expireOrder',
                'expectedOrderLineMethodCall' => 'cancelOrderLine',
            ),
            array(
                'orderStatus' => 'canceled',
                'orderLineStatus' => 'canceled',
                'expectedOrderMethodCall' => 'cancelOrder',
                'expectedOrderLineMethodCall' => 'cancelOrderLine',
            ),
            array(
                'orderStatus' => 'authorized',
                'orderLineStatus' => 'authorized',
                'expectedOrderMethodCall' => 'authorizeOrder',
                'expectedOrderLineMethodCall' => 'authorizeOrderLine',
            ),
            array(
                'orderStatus' => 'completed',
                'orderLineStatus' => 'completed',
                'expectedOrderMethodCall' => 'completeOrder',
                'expectedOrderLineMethodCall' => 'completeOrderLine',
            ),
        );
    }

    protected function setUpTestOrderReferences()
    {
        OrderReferenceService::getInstance()->updateOrderReference(
            $this->getOrderReferenceData(),
            $this->shopReference,
            PaymentMethodConfig::API_METHOD_ORDERS
        );
    }

    protected function getOrderReference()
    {
        return OrderReferenceService::getInstance()->getByShopReference($this->shopReference);
    }

    /**
     * @return Order
     */
    protected function getOrderReferenceData()
    {
        return Order::fromArray(json_decode($this->getMockOrderJson(), true));
    }

    /**
     * @return string
     */
    protected function getMockOrderJson()
    {
        return file_get_contents(__DIR__ . '/../../Common/ApiResponses/orderResponse.json');
    }
}
