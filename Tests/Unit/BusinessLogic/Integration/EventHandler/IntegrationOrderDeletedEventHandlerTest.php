<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Integration\EventHandler;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Event\IntegrationOrderDeletedEvent;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\Model\Notification;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Model\OrderReference;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\IntegrationEventHandler\IntegrationOrderDeletedEventHandler;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\QueryFilter\Operators;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\QueryFilter\QueryFilter;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\RepositoryRegistry;

class IntegrationOrderDeletedEventHandlerTest extends IntegrationOrderEventHandlerTest
{

    /**
     * @var IntegrationOrderDeletedEventHandler
     */
    protected $handler;

    public function setUp()
    {
        parent::setUp();
        $this->handler = new IntegrationOrderDeletedEventHandler();
    }

    /**
     * @throws \Exception
     */
    public function testOrderDeleted()
    {
        $event = new IntegrationOrderDeletedEvent($this->orderShopReference);
        $this->handler->handle($event);

        $notifications = $this->defaultChannel->get(10, 0);
        $this->assertCount(1, $notifications);
        /** @var Notification $notification */
        $notification = reset($notifications);

        $this->assertEquals(
            'mollie.payment.integration.event.notification.order_deleted.title',
            $notification->getMessage()->getMessageKey()
        );

        $this->assertEquals(
            'mollie.payment.integration.event.notification.order_deleted.description',
            $notification->getDescription()->getMessageKey()
        );

        $this->assertEquals($this->orderShopReference, $notification->getOrderNumber());

        $filter = new QueryFilter();
        $filter->where('shopReference', Operators::EQUALS, $this->orderShopReference);

        $reference = RepositoryRegistry::getRepository(OrderReference::CLASS_NAME)->selectOne($filter);
        $this->assertNull($reference);
    }

    /**
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testOrderDeleteWithNonExistingReference()
    {
        $event = new IntegrationOrderDeletedEvent('non_exist');
        $this->handler->handle($event);

        $notifications = $this->defaultChannel->get(10, 0);
        $this->assertCount(0, $notifications);
    }
}
