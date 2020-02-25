<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Payments\WebHookHandler;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Interfaces\OrderTransitionService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\WebHook\PaymentChangedWebHookEvent;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;

class StatusWebHookHandler
{
    private static $STATUS_TO_SERVICE_METHOD = array(
        'paid' => 'payOrder',
        'expired' => 'expireOrder',
        'canceled' => 'cancelOrder',
        'authorized' => 'authorizeOrder',
        'failed' => 'failOrder',
    );

    public function handle(PaymentChangedWebHookEvent $event)
    {
        if ($event->getCurrentPayment()->getStatus() === $event->getNewPayment()->getStatus()) {
            return;
        }

        $serviceMethod = $this->getServiceMethodFor($event->getNewPayment()->getStatus());
        if (!$serviceMethod) {
            return;
        }

        call_user_func(
            array($this->getOrderTransitionService(), $serviceMethod),
            $event->getOrderReference()->getShopReference(),
            $event->getNewPayment()->getMetadata()
        );
    }

    /**
     * @param string $status
     *
     * @return string|null
     */
    protected function getServiceMethodFor($status)
    {
        return array_key_exists($status, static::$STATUS_TO_SERVICE_METHOD) ? static::$STATUS_TO_SERVICE_METHOD[$status] : null;
    }

    /**
     * @return OrderTransitionService
     */
    protected function getOrderTransitionService()
    {
        /** @var OrderTransitionService $orderTransitionService */
        $orderTransitionService = ServiceRegister::getService(OrderTransitionService::CLASS_NAME);
        return $orderTransitionService;
    }
}