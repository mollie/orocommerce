<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\WebHook\Handler;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Order;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Refunds\Refund;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Interfaces\OrderLineTransitionService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Interfaces\OrderTransitionService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Model\OrderReference;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\OrderReferenceService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Refunds\WebHookHandler\OrderLineRefundWebHookHandler;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\WebHook\OrderChangedWebHookEvent;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\RepositoryRegistry;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\BaseTestWithServices;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\ORM\MemoryRepository;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\TestOrderLineTransitionService;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\TestOrderTransitionService;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestServiceRegister;

class OrderLineRefundWebHookHandlerTest extends BaseTestWithServices
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
     * @var OrderLineRefundWebHookHandler
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

        $this->handler = new OrderLineRefundWebHookHandler();
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

    public function testRefundStatusChangeDetection()
    {
        $newOrder = $this->getOrderReferenceData();
        $this->setRefundStatus($newOrder, 'refunded');
        $event = new OrderChangedWebHookEvent($this->getOrderReference(), $newOrder);

        $this->handler->handle($event);

        $this->assertCount(0, $this->orderTransitionService->getCallHistory());
        $callHistory = $this->orderLineTransitionService->getCallHistory();
        $this->assertCount(1, $callHistory);
        $this->assertArrayHasKey('refundOrderLine', $callHistory);
        $this->assertCount(1, $callHistory['refundOrderLine']);
    }

    public function testRefundStatusChangeIgnored()
    {
        $newOrder = $this->getOrderReferenceData();
        $this->setRefundStatus($newOrder, 'processing');
        $event = new OrderChangedWebHookEvent($this->getOrderReference(), $newOrder);
        $this->handler->handle($event);
        $this->assertCount(0, $this->orderTransitionService->getCallHistory());
        $this->assertCount(0, $this->orderLineTransitionService->getCallHistory());
    }

    public function testFullRefundOrder()
    {
        $newOrder = $this->getOrderReferenceData();
        $embedded = $newOrder->getEmbedded();
        /** @var Refund $refund */
        $refund = reset($embedded['refunds']);
        $lines = $newOrder->getLines();

        $refund->setLines($lines);

        $this->setRefundStatus($newOrder, 'refunded');

        $event = new OrderChangedWebHookEvent($this->getOrderReference(), $newOrder);
        $this->handler->handle($event);

        $orderTransitionCallHistory = $this->orderTransitionService->getCallHistory();

        $this->assertCount(1, $orderTransitionCallHistory);
        $this->assertArrayHasKey('refundOrder', $orderTransitionCallHistory);
        $this->assertCount(1, $orderTransitionCallHistory['refundOrder']);

        $orderLineTransitionCallHistory = $this->orderLineTransitionService->getCallHistory();
        $this->assertCount(1, $orderLineTransitionCallHistory);
        $this->assertArrayHasKey('refundOrderLine', $orderLineTransitionCallHistory);
        $this->assertCount(3, $orderLineTransitionCallHistory['refundOrderLine']);
    }

    protected function setUpTestOrderReferences()
    {
        OrderReferenceService::getInstance()->updateOrderReference(
            $this->getOrderReferenceData(),
            $this->shopReference,
            PaymentMethodConfig::API_METHOD_ORDERS
        );
    }

    /**
     * @return Order
     */
    protected function getOrderReferenceData()
    {
        return Order::fromArray(json_decode($this->getMockOrderJson(), true));
    }

    protected function getOrderReference()
    {
        return OrderReferenceService::getInstance()->getByShopReference($this->shopReference);
    }

    /**
     * @return string
     */
    protected function getMockOrderJson()
    {
        return file_get_contents(__DIR__ . '/../../Common/ApiResponses/orderResponse.json');
    }

    protected function setRefundStatus(Order $newOrder, $status)
    {
        $embedded = $newOrder->getEmbedded();
        /** @var Refund $refund */
        $refund = reset($embedded['refunds']);
        $refund->setStatus($status);
    }
}
