<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\IntegrationEventHandler;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Order;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Event\IntegrationOrderClosedEvent;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Exceptions\OperationNotSupportedException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\NotificationHub;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\NotificationText;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Model\OrderReference;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\OrderReferenceService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;

/**
 * Class IntegrationOrderClosedEventHandler
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\IntegrationEventHandler
 */
class IntegrationOrderClosedEventHandler
{
    /**
     * @param IntegrationOrderClosedEvent $event
     *
     * @throws OperationNotSupportedException
     */
    public function handle(IntegrationOrderClosedEvent $event)
    {
        /** @var OrderReferenceService $orderReferenceService */
        $orderReferenceService = ServiceRegister::getService(OrderReferenceService::CLASS_NAME);
        $orderReference = $orderReferenceService->getByShopReference($event->getShopReference());
        if ($this->isActionRequired($orderReference)) {
            NotificationHub::pushInfo(
                new NotificationText('mollie.payment.integration.event.notification.order_closed.title'),
                new NotificationText('mollie.payment.integration.event.notification.order_closed.description'),
                $event->getShopReference()
            );

            throw new OperationNotSupportedException('Closing an order which is not completed is not supported');
        }
    }

    /**
     * Check if notification should be pushed
     *
     * @param OrderReference $orderReference
     *
     * @return bool
     */
    protected function isActionRequired($orderReference)
    {
        if ($orderReference === null) {
            return false;
        }

        if ($orderReference->getApiMethod() === PaymentMethodConfig::API_METHOD_ORDERS) {
            $order = Order::fromArray($orderReference->getPayload());

            return  $order->getStatus() !== 'completed';
        }

        return true;
    }
}
