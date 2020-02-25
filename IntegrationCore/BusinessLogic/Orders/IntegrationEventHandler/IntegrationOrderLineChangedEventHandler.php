<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\IntegrationEventHandler;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Event\IntegrationOrderLineChangedEvent;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\NotificationHub;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\NotificationText;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Exceptions\MollieReferenceNotFoundException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Exceptions\ReferenceNotFoundException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\OrderService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\Logger;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;

/**
 * Class IntegrationOrderLineChangedEventHandler
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\IntegrationEventHandler
 */
class IntegrationOrderLineChangedEventHandler
{
    /**
     * @param IntegrationOrderLineChangedEvent $event
     *
     * @throws \Exception
     */
    public function handle(IntegrationOrderLineChangedEvent $event)
    {
        try {
            $line = $event->getModifiedOrderLine();
            if ($line->getId() === null) {
                throw  new MollieReferenceNotFoundException('Order line change event ignored. Event order line is missing id.');
            }

            /** @var OrderService $orderService */
            $orderService = ServiceRegister::getService(OrderService::CLASS_NAME);
            $orderService->updateOrderLine($event->getShopReference(), $line);
        } catch (ReferenceNotFoundException $exception) {
            // Intentionally left blank. Not existing shop reference should be skipped silently
        } catch (\Exception $exception) {
            $this->handleOrderLineChangeError($event, $exception);
        }
    }

    /**
     * @param IntegrationOrderLineChangedEvent $event
     * @param \Exception $e
     *
     * @throws \Exception
     */
    protected function handleOrderLineChangeError(IntegrationOrderLineChangedEvent $event, \Exception $e)
    {
        Logger::logError(
            'Failed to cancel mollie order.',
            'Core',
            array(
                'ShopOrderReference' => $event->getShopReference(),
                'ModifiedOrderLineData' => $event->getModifiedOrderLine() ? $event->getModifiedOrderLine()->toArray() : null,
                'ExceptionMessage' => $e->getMessage(),
                'ExceptionTrace' => $e->getTraceAsString(),
            )
        );
        NotificationHub::pushInfo(
            new NotificationText('mollie.payment.integration.event.notification.order_line_changed_error.title'),
            new NotificationText(
                'mollie.payment.integration.event.notification.order_line_changed_error.description',
                array('api_message' => $e->getMessage())
            ),
            $event->getShopReference()
        );

        throw $e;
    }
}
