<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\IntegrationEventHandler;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\NotificationHub;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\NotificationText;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\OrderService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\Logger;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Utility\Events\Event;

/**
 * Class IntegrationOrderAddressChangedEventHandler
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\IntegrationEventHandler
 */
abstract class IntegrationOrderAddressChangedEventHandler
{
    const SHIPPING_ADDRESS = 'shipping';
    const BILLING_ADDRESS = 'billing';

    /**
     * @var OrderService
     */
    protected $orderService;

    /**
     * @param Event $event
     * @param \Exception $e
     *
     * @param $addressType
     *
     * @throws \Exception
     */
    protected function handleAddressUpdateError(Event $event, \Exception $e, $addressType)
    {
        Logger::logError(
            'Failed to update address.',
            'Core',
            array(
                'ShopOrderReference' => $event->getShopReference(),
                'ExceptionMessage' => $e->getMessage(),
                'ExceptionTrace' => $e->getTraceAsString(),
            )
        );
        NotificationHub::pushInfo(
            new NotificationText("mollie.payment.integration.event.notification.{$addressType}_address_change_error.title"),
            new NotificationText(
                "mollie.payment.integration.event.notification.{$addressType}_address_change_error.description",
                array('api_message' => $e->getMessage())
            ),
            $event->getShopReference()
        );

        throw $e;
    }

    /**
     * @return OrderService
     */
    protected function getOrderService()
    {
        if ($this->orderService === null) {
            $this->orderService = ServiceRegister::getService(OrderService::CLASS_NAME);
        }

        return $this->orderService;
    }
}
