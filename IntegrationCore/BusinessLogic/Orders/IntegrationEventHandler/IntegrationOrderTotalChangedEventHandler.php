<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\IntegrationEventHandler;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Event\IntegrationOrderTotalChangedEvent;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Exceptions\OperationNotSupportedException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\NotificationHub;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\NotificationText;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\OrderReferenceService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;

/**
 * Class IntegrationOrderTotalChangedEventHandler
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\IntegrationEventHandler
 */
class IntegrationOrderTotalChangedEventHandler
{

    /**
     * @param IntegrationOrderTotalChangedEvent $event
     *
     * @throws OperationNotSupportedException
     */
    public function handle(IntegrationOrderTotalChangedEvent $event)
    {
        /** @var OrderReferenceService $orderReferenceService */
        $orderReferenceService = ServiceRegister::getService(OrderReferenceService::CLASS_NAME);
        if ($orderReferenceService->getByShopReference($event->getShopReference())) {
            NotificationHub::pushInfo(
                new NotificationText('mollie.payment.integration.event.notification.order_total_changed.title'),
                new NotificationText('mollie.payment.integration.event.notification.order_total_changed.description'),
                $event->getShopReference()
            );

            throw new OperationNotSupportedException('Order total change is not supported on Mollie');
        }
    }
}
