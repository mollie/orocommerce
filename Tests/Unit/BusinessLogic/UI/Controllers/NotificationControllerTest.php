<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\UI\Controllers;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\Collections\ShopNotificationChannelCollection;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\DefaultNotificationChannel;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\Interfaces\DefaultNotificationChannelAdapter;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\Model\Notification;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\NotificationHub;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\NotificationText;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\UI\Controllers\NotificationController;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\QueryFilter\Operators;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\QueryFilter\QueryFilter;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\RepositoryRegistry;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\BaseTestWithServices;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\ORM\MemoryRepository;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestServiceRegister;

/**
 * Class NotificationControllerTest
 *
 * @package Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\UI\Controllers
 */
class NotificationControllerTest extends BaseTestWithServices
{
    /**
     * @var DefaultNotificationChannelAdapter
     */
    public $defaultChannel;
    /**
     * @var ShopNotificationChannelCollection
     */
    public $shopChannelsCollection;
    /**
     * @var NotificationController
     */
    private $notificationController;

    public function setUp()
    {
        parent::setUp();

        /** @noinspection PhpUnhandledExceptionInspection */
        RepositoryRegistry::registerRepository(Notification::getClassName(), MemoryRepository::getClassName());
        $me = $this;
        $this->defaultChannel = new DefaultNotificationChannel();
        $this->notificationController = new NotificationController();
        TestServiceRegister::registerService(DefaultNotificationChannelAdapter::CLASS_NAME, function () use ($me) {
            return $me->defaultChannel;
        });
        $this->shopChannelsCollection = new ShopNotificationChannelCollection();

        TestServiceRegister::registerService(ShopNotificationChannelCollection::CLASS_NAME, function () use ($me) {
            return $me->shopChannelsCollection;
        });
    }

    public function testGetNotificationsPaginated()
    {
        $this->pushNotifications();
        $response = $this->notificationController->get(3, 0);
        $this->assertEquals(3, $response->getTotalCount());
        $this->assertCount(3, $response->getNotifications());

        $response = $this->notificationController->get(1, 0);
        $this->assertEquals(3, $response->getTotalCount());
        $this->assertCount(1, $response->getNotifications());
        $notifications = $response->getNotifications();
        /** @var Notification $notification */
        $notification = reset($notifications);
        $this->assertEquals('TEST002', $notification->getOrderNumber());

        $response = $this->notificationController->get(1, 1);
        $this->assertEquals(3, $response->getTotalCount());
        $this->assertCount(1, $response->getNotifications());
        $notifications = $response->getNotifications();
        /** @var Notification $notification */
        $notification = reset($notifications);
        $this->assertEquals('TEST001', $notification->getOrderNumber());
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    public function testMarkers()
    {
        $this->pushNotifications();

        $repository = RepositoryRegistry::getRepository(Notification::getClassName());
        $filter = new QueryFilter();
        /** @noinspection PhpUnhandledExceptionInspection */
        $filter->where('websiteId', Operators::EQUALS, 'test');
        /** @var Notification $notification */
        $notification = $repository->selectOne($filter);
        $this->assertFalse($notification->isRead());
        $this->notificationController->markAsRead($notification->getId());
        $notification = $repository->selectOne($filter);
        $this->assertTrue($notification->isRead());
        $this->notificationController->markAsUnread($notification->getId());
        $notification = $repository->selectOne($filter);
        $this->assertFalse($notification->isRead());
    }

    private function pushNotifications()
    {
        for ($i = 0; $i < 3; $i++) {
            NotificationHub::pushError(new NotificationText("test.msg$i"), new NotificationText("test.desc$i"), "TEST00$i");
        }
    }
}
