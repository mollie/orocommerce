<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\Interfaces;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\Model\Notification;

/**
 * Interface NotificationChannel
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\Interfaces
 */
interface NotificationChannelAdapter
{

    /**
     *
     * @param Notification $notification
     */
    public function push(Notification $notification);
}
