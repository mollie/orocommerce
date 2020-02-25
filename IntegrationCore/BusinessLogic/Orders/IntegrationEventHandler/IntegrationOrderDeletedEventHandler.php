<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\IntegrationEventHandler;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Event\IntegrationOrderDeletedEvent;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\NotificationHub;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\NotificationText;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Model\OrderReference;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\OrderReferenceService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\RepositoryRegistry;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;

/**
 * Class IntegrationOrderDeletedEventHandler
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\IntegrationEventHandler
 */
class IntegrationOrderDeletedEventHandler
{
    /**
     * @param IntegrationOrderDeletedEvent $event
     *
     * @throws RepositoryNotRegisteredException
     */
    public function handle(IntegrationOrderDeletedEvent $event)
    {
        /** @var OrderReferenceService $orderReferenceService */
        $orderReferenceService = ServiceRegister::getService(OrderReferenceService::CLASS_NAME);
        if ($orderReference = $orderReferenceService->getByShopReference($event->getShopReference())) {
            NotificationHub::pushInfo(
                new NotificationText('mollie.payment.integration.event.notification.order_deleted.title'),
                new NotificationText('mollie.payment.integration.event.notification.order_deleted.description'),
                $event->getShopReference()
            );

            /** @var RepositoryInterface $repository */
            $repository = RepositoryRegistry::getRepository(OrderReference::CLASS_NAME);
            $repository->delete($orderReference);
        }
    }
}
