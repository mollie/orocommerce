<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\IntegrationEventHandler;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Event\IntegrationOrderShippedEvent;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\NotificationHub;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\NotificationText;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Exceptions\ReferenceNotFoundException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\OrderService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\Logger;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;

/**
 * Class IntegrationOrderShippedEventHandler
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\IntegrationEventHandler
 */
class IntegrationOrderShippedEventHandler
{
    /**
     * @param IntegrationOrderShippedEvent $event
     *
     * @throws \Exception
     */
    public function handle(IntegrationOrderShippedEvent $event)
    {
        /** @var OrderService $orderService */
        $orderService = ServiceRegister::getService(OrderService::CLASS_NAME);
        try {
            $orderService->shipOrder($event->getShopOrderReference(), $event->getTracking());
        } catch (ReferenceNotFoundException $e) {
            // Intentionally left blank. Not existing shop reference should be skipped silently
        } catch (\Exception $e) {
            Logger::logError(
                'Failed to create mollie order shipment.',
                'Core',
                array(
                    'ShopOrderReference' => $event->getShopOrderReference(),
                    'TrackingData' => $event->getTracking() ? $event->getTracking()->toArray() : null,
                    'ExceptionMessage' => $e->getMessage(),
                    'ExceptionTrace' => $e->getTraceAsString(),
                )
            );
            NotificationHub::pushInfo(
                new NotificationText('mollie.payment.integration.event.notification.order_ship_error.title'),
                new NotificationText(
                    'mollie.payment.integration.event.notification.order_ship_error.description',
                    array('api_message' => $e->getMessage())
                ),
                $event->getShopOrderReference()
            );

            throw $e;
        }
    }
}
