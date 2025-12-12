<?php

namespace Mollie\Bundle\PaymentBundle\Controller;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\OrderService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Configuration\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\CallbackReturnEvent;
use Oro\Bundle\PaymentBundle\Event\CallbackErrorEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;


class CallbackController extends AbstractController
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var Configuration
     */
    private $configService;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param RouterInterface $router
     * @param Configuration $configService
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, RouterInterface $router, Configuration $configService)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->router = $router;
        $this->configService = $configService;
    }

    /**
     * @Route(
     * "/return/{accessIdentifier}",
     * name="mollie_payment_callback_return",
     * requirements={"accessIdentifier"="[a-zA-Z0-9\-]+"},
     * methods={"GET", "POST"}
     * )
     * @ParamConverter("paymentTransaction", options={"mapping": {"accessIdentifier": "accessIdentifier"}})
     * @param PaymentTransaction $transaction
     * @param Request $request
     *
     * @return Response
     */
    public function mollieCallbackAction(PaymentTransaction $transaction, Request $request)
    {
        $shopReference = $transaction->getEntityIdentifier();

        if (!$shopReference) {
            return $this->handleCallbackError($transaction, $request);
        }

        try {
            /** @var OrderService $orderService */
            $orderService = ServiceRegister::getService(OrderService::CLASS_NAME);
            $mollieOrder = $this->configService->doWithContext($this->getChannelIdFromTransaction($transaction), function () use ($orderService, $shopReference) {
                return $orderService->getOrder($shopReference);
            });

            $unsuccessfulPayments = array_filter($mollieOrder->getEmbedded()['payments'], function ($payment) {
                return in_array($payment->getStatus(), ['failed', 'canceled']);
            });
            $isSuccessful = empty($unsuccessfulPayments);

            if ($isSuccessful) {
                return $this->handleCallbackReturn($transaction);
            }

            return $this->handleCallbackError($transaction, $request);
        } catch (\Throwable $e) {
            return $this->handleCallbackError($transaction, $request);
        }
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     *
     * @return Response
     */
    private function handleCallbackReturn(PaymentTransaction $paymentTransaction)
    {
        $event = new CallbackReturnEvent((array)$paymentTransaction->getPaymentMethod());
        $event->setPaymentTransaction($paymentTransaction);

        $this->eventDispatcher->dispatch($event, CallbackReturnEvent::NAME);

        if ($event->getResponse()) {
            return $event->getResponse();
        }

        return new RedirectResponse($this->router->generate('oro_checkout_frontend_checkout'));
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     *
     * @return Response
     */
    private function handleCallbackError(PaymentTransaction $paymentTransaction, Request $request)
    {
        if ($request->hasSession()) {
            $request->getSession()->getFlashBag()->add(
                'error',
                'Your payment could not be processed. Please try again.'
            );
        }

        $event = new CallbackErrorEvent((array)$paymentTransaction->getPaymentMethod());
        $event->setPaymentTransaction($paymentTransaction);

        $this->eventDispatcher->dispatch($event, CallbackErrorEvent::NAME);

        if ($event->getResponse()) {
            return $event->getResponse();
        }

        return new RedirectResponse($this->router->generate('oro_checkout_frontend_checkout'));
    }

    /**
     * @param PaymentTransaction $transaction
     *
     * @return int|null
     */
    private function getChannelIdFromTransaction(PaymentTransaction $transaction): ?int
    {
        if (preg_match('/^mollie_payment_(\d+)_/', $transaction->getPaymentMethod(), $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}
