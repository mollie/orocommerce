<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\IntegrationEventHandler;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Event\IntegrationOrderShippingAddressChangedEvent;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Exceptions\ReferenceNotFoundException;

/**
 * Class IntegrationOrderShippingAddressChangedEventHandler
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\IntegrationEventHandler
 */
class IntegrationOrderShippingAddressChangedEventHandler extends IntegrationOrderAddressChangedEventHandler
{
    /**
     * @param IntegrationOrderShippingAddressChangedEvent $event
     *
     * @throws \Exception
     */
    public function handle(IntegrationOrderShippingAddressChangedEvent $event)
    {
        try {
            $this->getOrderService()->updateShippingAddress($event->getShopReference(), $event->getShippingAddress());
        } catch (ReferenceNotFoundException $exception) {
        } catch (\Exception $exception) {
            $this->handleAddressUpdateError($event, $exception, static::SHIPPING_ADDRESS);
        }
    }
}
