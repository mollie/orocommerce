<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Integration\EventHandler;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Event\IntegrationOrderTotalChangedEvent;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\Model\Notification;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\IntegrationEventHandler\IntegrationOrderTotalChangedEventHandler;

class IntegrationOrderTotalChangedEventHandlerTest extends IntegrationOrderEventHandlerTest
{
    /**
     * @var IntegrationOrderTotalChangedEventHandler
     */
    protected $handler;

    public function setUp()
    {
        parent::setUp();
        $this->handler = new IntegrationOrderTotalChangedEventHandler();
    }

    /**
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Exceptions\OperationNotSupportedException
     */
    public function testOrderTotalChanged()
    {
        $event = new IntegrationOrderTotalChangedEvent($this->orderShopReference);
        $this->expectException('Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Exceptions\OperationNotSupportedException');
        $this->handler->handle($event);
        $notifications = $this->defaultChannel->get(10, 0);
        $this->assertCount(1, $notifications);
        /** @var Notification $notification */
        $notification = reset($notifications);

        $this->assertEquals(
            'mollie.payment.integration.event.notification.order_total_changed.title',
            $notification->getMessage()->getMessageKey()
        );

        $this->assertEquals(
            'mollie.payment.integration.event.notification.order_total_changed.description',
            $notification->getDescription()->getMessageKey()
        );

        $this->assertEquals($this->orderShopReference, $notification->getOrderNumber());
    }
}
