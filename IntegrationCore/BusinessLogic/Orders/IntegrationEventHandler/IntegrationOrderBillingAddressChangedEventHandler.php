<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\IntegrationEventHandler;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Event\IntegrationOrderBillingAddressChangedEvent;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Exceptions\ReferenceNotFoundException;

/**
 * Class IntegrationOrderBillingAddressChangedEventHandler
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\IntegrationEventHandler
 */
class IntegrationOrderBillingAddressChangedEventHandler extends IntegrationOrderAddressChangedEventHandler
{
    /**
     * @param IntegrationOrderBillingAddressChangedEvent $event
     *
     * @throws \Exception
     */
    public function handle(IntegrationOrderBillingAddressChangedEvent $event)
    {
        try {
            $this->getOrderService()->updateBillingAddress($event->getShopReference(), $event->getBillingAddress());
        } catch (ReferenceNotFoundException $exception) {

        } catch (\Exception $exception) {
            $this->handleAddressUpdateError($event, $exception, static::BILLING_ADDRESS);
        }
    }
}
