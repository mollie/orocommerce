<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\Interfaces\ShopNotificationChannelAdapter;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\Model\Notification;

/**
 * Class TestShopNotificationChannelPrimary
 *
 * @package Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents
 */
class TestShopNotificationChannel implements ShopNotificationChannelAdapter
{
    private $storage = array();
    private static $internalId = 0;

    public function push(Notification $notification)
    {
        $notification->setId(self::$internalId++);
        $this->storage[] = $notification;
    }

    public function getStorage()
    {
        return $this->storage;
    }

    public function resetStorage()
    {
        $this->storage = array();
    }
}
