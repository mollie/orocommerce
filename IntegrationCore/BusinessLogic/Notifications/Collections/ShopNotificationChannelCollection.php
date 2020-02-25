<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\Collections;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\Interfaces\ShopNotificationChannelAdapter;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\Model\Notification;

/**
 * Class ShopNotificationChannelCollection
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\Collections
 */
class ShopNotificationChannelCollection implements ShopNotificationChannelAdapter
{
    /**
     * @var ShopNotificationChannelAdapter[]
     */
    private $notificationsChannels = array();

    /**
     * @param Notification $notification
     */
    public function push(Notification $notification)
    {
        foreach ($this->notificationsChannels as $shopNotificationChannel) {
            $shopNotificationChannel->push($notification);
        }
    }

    /**
     * Adds shop specific notification channel
     *
     * @param ShopNotificationChannelAdapter $shopNotificationChannel
     */
    public function addChannel(ShopNotificationChannelAdapter $shopNotificationChannel)
    {
        $this->notificationsChannels[] = $shopNotificationChannel;
    }

    /**
     * Returns all registered shop specific notification channels
     *
     * @return ShopNotificationChannelAdapter[]
     */
    public function getChannels()
    {
        return $this->notificationsChannels;
    }
}
