<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Notifications;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\Collections\ShopNotificationChannelCollection;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\DefaultNotificationChannel;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\Interfaces\DefaultNotificationChannelAdapter;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\Interfaces\ShopNotificationChannelAdapter;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\Model\Notification;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\NotificationHub;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\NotificationText;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryClassException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\RepositoryRegistry;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\BaseTestWithServices;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\ORM\MemoryRepository;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\TestShopNotificationChannel;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestServiceRegister;

/**
 * Class NotificationHubTest
 *
 * @package Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Notifications
 */
class NotificationHubTest extends BaseTestWithServices
{
    const TEST_MESSAGE_KEY = 'test.message';
    const TEST_DESCRIPTION_KEY = 'test.desc';
    const TEST_ORDER_NUMBER = 'TEST001';


    /**
     * @var DefaultNotificationChannelAdapter
     */
    public $defaultChannel;
    /**
     * @var ShopNotificationChannelCollection
     */
    public $shopChannelsCollection;

    /**
     * @var TestShopNotificationChannel
     */
    public $testShopChannel1;

    /**
     * @var TestShopNotificationChannel
     */
    public $testShopChannel2;
    /**
     * @var NotificationText
     */
    protected $message;
    /**
     * @var NotificationText
     */
    protected $description;

    /**
     * {@inheritdoc}
     *
     * @throws RepositoryClassException
     */
    public function setUp()
    {
        parent::setUp();
        /** @noinspection PhpUnhandledExceptionInspection */
        RepositoryRegistry::registerRepository(Notification::getClassName(), MemoryRepository::getClassName());
        $me = $this;
        $this->defaultChannel = new DefaultNotificationChannel();
        $this->shopChannelsCollection = new ShopNotificationChannelCollection();
        $this->testShopChannel1 = new TestShopNotificationChannel();
        $this->testShopChannel2 = new TestShopNotificationChannel();

        TestServiceRegister::registerService(DefaultNotificationChannelAdapter::CLASS_NAME, function () use ($me) {
            return $me->defaultChannel;
        });

        TestServiceRegister::registerService(ShopNotificationChannelAdapter::CLASS_NAME, function () use ($me) {
            $me->shopChannelsCollection->addChannel($me->testShopChannel1);
            $me->shopChannelsCollection->addChannel($me->testShopChannel2);

            return $me->shopChannelsCollection;
        });

        $this->message = new NotificationText(self::TEST_MESSAGE_KEY, array(1));
        $this->description = new NotificationText(self::TEST_DESCRIPTION_KEY, array(1));
    }

    /**
     * @throws RepositoryNotRegisteredException
     */
    public function testPushingErrorNotificationWithoutShopSpecificChannels()
    {
        $this->registerEmptyShopNotificationCollection();
        NotificationHub::pushError($this->message, $this->description, self::TEST_ORDER_NUMBER);

        $allNotifications = $this->getNotificationsFromDefaultChannel();
        $this->assertCount(1, $allNotifications, 'Number of pushed notifications must be 1!');
        /** @var Notification $notification */
        $notification = reset($allNotifications);
        $this->assertFalse($notification->isRead());
        $this->assertEquals(NotificationHub::ERROR, $notification->getSeverity(), 'Notification severity must be error!');
        $this->assertEquals(self::TEST_ORDER_NUMBER, $notification->getOrderNumber(), 'Notification order number must be equal with self::TEST_ORDER_NUMBER!');
        $this->assertEquals('test', $notification->getWebsiteId(), 'Notification website id must be equal with self::TEST_WEBSITE!');
        $message = $notification->getMessage();
        $this->assertEquals(self::TEST_MESSAGE_KEY, $message->getMessageKey(), 'Message key must be equal with self::TEST_MESSAGE_KEY');
    }

    /**
     * @throws RepositoryNotRegisteredException
     */
    public function testPushingWarningNotification()
    {
        NotificationHub::pushWarning($this->message, $this->description, self::TEST_ORDER_NUMBER);

        $notifications = $this->getNotificationsFromDefaultChannel();
        $this->assertCount(1, $notifications);
        /** @var Notification $notification */
        $notification = reset($notifications);
        $this->assertEquals(NotificationHub::WARNING, $notification->getSeverity());
        $this->assertCount(1, $this->testShopChannel1->getStorage());
        $this->assertCount(1, $this->testShopChannel2->getStorage());
    }

    /**
     * @throws RepositoryNotRegisteredException
     */
    public function testPushingInfoNotification()
    {
        NotificationHub::pushInfo($this->message, $this->description, self::TEST_ORDER_NUMBER);

        $notifications = $this->getNotificationsFromDefaultChannel();
        $this->assertCount(1, $notifications);
        /** @var Notification $notification */
        $notification = reset($notifications);
        $this->assertEquals(NotificationHub::INFO, $notification->getSeverity());

        $this->assertCount(1, $notifications);
        $this->assertCount(0, $this->testShopChannel1->getStorage());
        $this->assertCount(0, $this->testShopChannel2->getStorage());
    }

    public function registerEmptyShopNotificationCollection()
    {
        $me = $this;
        TestServiceRegister::registerService(ShopNotificationChannelCollection::CLASS_NAME, function () use ($me) {
            return $me->shopChannelsCollection;
        });
    }

    /**
     * @return Notification[]
     * @throws RepositoryNotRegisteredException
     */
    public function getNotificationsFromDefaultChannel()
    {
        /** @var Notification[] $notifications */
        $notifications = RepositoryRegistry::getRepository(Notification::getClassName())->select();

        return $notifications;
    }
}
