<?php

namespace Mollie\Bundle\PaymentBundle\EventListener\Callback;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\NotificationHub;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\NotificationText;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\WebHook\WebHookContext;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\WebHook\WebHookTransformer;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Configuration\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\Logger;
use Mollie\Bundle\PaymentBundle\PaymentMethod\MolliePayment;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Provider\MolliePaymentProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\AbstractCallbackEvent;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class PaymentCheckoutCallbackListener
 *
 * @package Mollie\Bundle\PaymentBundle\EventListener\Callback
 */
class PaymentCheckoutCallbackListener
{
    /**
     * @var RequestStack
     */
    private $request;
    /**
     * @var MolliePaymentProvider
     */
    protected $paymentMethodProvider;
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;
    /**
     * @var Configuration
     */
    private $configService;
    /**
     * @var WebHookTransformer
     */
    private $webhookTransformer;

    /**
     * PaymentCheckoutCallbackListener constructor.
     *
     * @param Configuration $configService
     * @param WebHookTransformer $webhookTransformer
     * @param RequestStack $request
     * @param MolliePaymentProvider $paymentMethodProvider
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        Configuration $configService,
        WebHookTransformer $webhookTransformer,
        RequestStack $request,
        MolliePaymentProvider $paymentMethodProvider,
        DoctrineHelper $doctrineHelper
    ) {
        $this->configService = $configService;
        $this->webhookTransformer = $webhookTransformer;
        $this->request = $request;
        $this->paymentMethodProvider = $paymentMethodProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param AbstractCallbackEvent $event
     */
    public function onNotify(AbstractCallbackEvent $event)
    {
        WebHookContext::start();
        $this->handleEvent($event);
        WebHookContext::stop();
    }

    /**
     * @param AbstractCallbackEvent $event
     */
    protected function handleEvent(AbstractCallbackEvent $event)
    {
        try {
            Logger::logDebug(
                'Web hook detected. Web hook event listener fired.',
                'Integration',
                [
                    'eventName' => $event->getEventName(),
                    'eventData' => $event->getData(),
                ]
            );

            $paymentTransaction = $event->getPaymentTransaction();
            if (!$paymentTransaction) {
                Logger::logWarning(
                    'Web hook without payment transaction detected.',
                    'Integration',
                    [
                        'eventName' => $event->getEventName(),
                        'eventData' => $event->getData(),
                    ]
                );
                return;
            }

            if (!$this->request->getMainRequest()) {
                Logger::logWarning(
                    'Web hook without master HTTP request detected.',
                    'Integration',
                    [
                        'eventName' => $event->getEventName(),
                        'eventData' => $event->getData(),
                    ]
                );
                return;
            }

            $paymentMethodId = $paymentTransaction->getPaymentMethod();
            if (false === $this->paymentMethodProvider->hasPaymentMethod($paymentMethodId)) {
                Logger::logWarning(
                    'Web hook without payment method detected.',
                    'Integration',
                    [
                        'eventName' => $event->getEventName(),
                        'eventData' => $event->getData(),
                        'paymentMethodId' => $paymentMethodId,
                    ]
                );
                return;
            }

            /** @var MolliePayment $paymentMethod */
            $paymentMethod = $this->paymentMethodProvider->getPaymentMethod($paymentMethodId);
            $webHookPayload = $this->request->getMainRequest()->getContent();

            /** @var Order $order */
            $order = $this->doctrineHelper->getEntity(
                $paymentTransaction->getEntityClass(),
                $paymentTransaction->getEntityIdentifier()
            );

            if (!$order) {
                $this->handleMissingOrder($event, $paymentTransaction);
                return;
            }

            $this->configService->doWithContext(
                (string)$paymentMethod->getConfig()->getChannelId(),
                function () use ($webHookPayload) {
                    $this->webhookTransformer->handle($webHookPayload);
                }
            );

            if ($this->doctrineHelper->getEntityManager($order)) {
                $this->doctrineHelper->getEntityManager($order)->flush($order);
            }

            if ($this->doctrineHelper->getEntityManager($paymentTransaction)) {
                $this->doctrineHelper->getEntityManager($paymentTransaction)->flush($paymentTransaction);
            }

            $event->markSuccessful();
        } catch (\Exception $e) {
            $paymentTransaction = $event->getPaymentTransaction();
            $paymentTransaction->setSuccessful(false);
            Logger::logError(
                'Web hook processing failed.',
                'Integration',
                [
                    'ExceptionMessage' => $e->getMessage(),
                    'ExceptionTrace' => $e->getTraceAsString(),
                ]
            );
        }
    }

    /**
     * @param AbstractCallbackEvent $event
     * @param PaymentTransaction $paymentTransaction
     */
    private function handleMissingOrder(AbstractCallbackEvent $event, PaymentTransaction $paymentTransaction)
    {
        Logger::logWarning(
            'Web hook without order detected. Order does not exist in the system anymore.',
            'Integration',
            [
                'eventName' => $event->getEventName(),
                'eventData' => $event->getData(),
                'orderId' => $paymentTransaction->getEntityIdentifier(),
            ]
        );

        $event->stopPropagation();
        $event->markSuccessful();

        /** @var MolliePayment $paymentMethod */
        $paymentMethod = $this->paymentMethodProvider->getPaymentMethod($paymentTransaction->getPaymentMethod());

        $this->configService->doWithContext(
            (string)$paymentMethod->getConfig()->getChannelId(),
            function () use ($paymentTransaction) {
                NotificationHub::pushError(
                    new NotificationText('mollie.payment.webhook.notification.invalid_shop_order.title'),
                    new NotificationText('mollie.payment.webhook.notification.invalid_shop_order.description'),
                    $paymentTransaction->getEntityIdentifier()
                );
            }
        );
    }
}
