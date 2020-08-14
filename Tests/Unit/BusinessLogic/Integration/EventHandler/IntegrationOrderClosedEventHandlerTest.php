<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Integration\EventHandler;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Event\IntegrationOrderClosedEvent;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\Model\Notification;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\OrderReferenceService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\IntegrationEventHandler\IntegrationOrderClosedEventHandler;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;

class IntegrationOrderClosedEventHandlerTest extends IntegrationOrderEventHandlerTest
{

    /**
     * @var IntegrationOrderClosedEventHandler
     */
    protected $handler;

    public function setUp()
    {
        parent::setUp();
        $this->handler = new IntegrationOrderClosedEventHandler();
    }

    /**
     * @throws \Exception
     */
    public function testOrderClose()
    {
        $event = new IntegrationOrderClosedEvent($this->orderShopReference);
        $this->expectException('Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Exceptions\OperationNotSupportedException');
        $this->handler->handle($event);

        $notifications = $this->defaultChannel->get(10, 0);
        $this->assertCount(1, $notifications);
        /** @var Notification $notification */
        $notification = reset($notifications);

        $this->assertEquals(
            'mollie.payment.integration.event.notification.order_closed.title',
            $notification->getMessage()->getMessageKey()
        );

        $this->assertEquals(
            'mollie.payment.integration.event.notification.order_closed.description',
            $notification->getDescription()->getMessageKey()
        );

        $this->assertEquals($this->orderShopReference, $notification->getOrderNumber());
    }

    /**
     * @throws \Exception
     */
    public function testShippedOrderClosed()
    {
        $order = $this->getOrderReferenceOrderData();
        $order->setStatus('completed');
        OrderReferenceService::getInstance()->updateOrderReference(
            $order,
            $this->orderShopReference,
            PaymentMethodConfig::API_METHOD_ORDERS
        );
        $event = new IntegrationOrderClosedEvent($this->orderShopReference);
        $this->handler->handle($event);
        $notifications = $this->defaultChannel->get(10, 0);
        $this->assertCount(0, $notifications);
    }

    /**
     * @throws \Exception
     */
    public function testOrderCloseWithNonExistingReference()
    {
        $event = new IntegrationOrderClosedEvent('not_exist');
        $this->handler->handle($event);
        $notifications = $this->defaultChannel->get(10, 0);
        $this->assertCount(0, $notifications);
    }

    protected function getErrorJson()
    {
        return file_get_contents(__DIR__ . '/../../Common/ApiResponses/errorOrderCancel.json');
    }
}
