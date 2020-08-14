<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\WebHook\Handler;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Order;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Interfaces\OrderLineTransitionService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Interfaces\OrderTransitionService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Model\OrderReference;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\OrderReferenceService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\WebHookHandler\LineStatusWebHookHandler;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\WebHook\OrderChangedWebHookEvent;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\RepositoryRegistry;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\BaseTestWithServices;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\ORM\MemoryRepository;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\TestOrderLineTransitionService;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\TestOrderTransitionService;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestServiceRegister;

class OrderLIneStatusWebHookHandlerTest extends BaseTestWithServices
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
     * @var LineStatusWebHookHandler
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

        $this->handler = new LineStatusWebHookHandler();
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
     * @param string $orderLineStatus
     * @param string $expectedOrderLineMethodCall
     *
     * @dataProvider orderLineStatusProvider
     */
    public function testOrderLineStatusChangeDetection(
        $orderLineStatus,
        $expectedOrderLineMethodCall
    ) {
        $newOrder = $this->getOrderReferenceData();
        $newOrderLines = $newOrder->getLines();
        $newOrderLines[0]->setStatus($orderLineStatus);

        $event = new OrderChangedWebHookEvent($this->getOrderReference(), $newOrder);

        $this->handler->handle($event);

        $this->assertCount(0, $this->orderTransitionService->getCallHistory());
        $callHistory = $this->orderLineTransitionService->getCallHistory();
        $this->assertCount(1, $callHistory);
        $this->assertArrayHasKey(
            $expectedOrderLineMethodCall,
            $callHistory,
            "Order status change to '{$orderLineStatus}' should trigger OrderTransitionService::{$expectedOrderLineMethodCall}() method."
        );
        $this->assertCount(1, $callHistory[$expectedOrderLineMethodCall]);
    }

    public function testChangedOrderStatusIsIgnored()
    {
        $newOrder = $this->getOrderReferenceData();
        $newOrder->setStatus('paid');
        $newOrderLines = $newOrder->getLines();
        $newOrderLines[0]->setStatus('paid');
        $event = new OrderChangedWebHookEvent($this->getOrderReference(), $newOrder);

        $this->handler->handle($event);

        $callHistory = $this->orderLineTransitionService->getCallHistory();
        $this->assertCount(0, $callHistory);
    }

    public function testNotExistingOrderReferenceLineIsIgnored()
    {
        $newOrder = $this->getOrderReferenceData();
        $newOrderLines = $newOrder->getLines();
        $newOrderLines[0]->setStatus('paid');
        $newOrderLines[0]->setId('changed_unknown_id');
        $event = new OrderChangedWebHookEvent($this->getOrderReference(), $newOrder);

        $this->handler->handle($event);

        $callHistory = $this->orderLineTransitionService->getCallHistory();
        $this->assertCount(0, $callHistory);
    }

    public function testUnknownOrderLineStatus()
    {
        $newOrder = $this->getOrderReferenceData();
        $newOrderLines = $newOrder->getLines();
        $newOrderLines[0]->setStatus('test_unknown_status');
        $event = new OrderChangedWebHookEvent($this->getOrderReference(), $newOrder);

        $this->handler->handle($event);

        $callHistory = $this->orderLineTransitionService->getCallHistory();
        $this->assertCount(0, $callHistory);
    }

    public function orderLineStatusProvider()
    {
        return array(
            array(
                'orderLineStatus' => 'paid',
                'expectedOrderLineMethodCall' => 'payOrderLine',
            ),
            array(
                'orderLineStatus' => 'canceled',
                'expectedOrderLineMethodCall' => 'cancelOrderLine',
            ),
            array(
                'orderLineStatus' => 'canceled',
                'expectedOrderLineMethodCall' => 'cancelOrderLine',
            ),
            array(
                'orderLineStatus' => 'authorized',
                'expectedOrderLineMethodCall' => 'authorizeOrderLine',
            ),
            array(
                'orderLineStatus' => 'completed',
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
