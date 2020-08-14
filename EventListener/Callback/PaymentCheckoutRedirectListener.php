<?php

namespace Mollie\Bundle\PaymentBundle\EventListener\Callback;

use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfigInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\AbstractCallbackEvent;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentResultMessageProviderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class PaymentCheckoutRedirectListener
 *
 * @package Mollie\Bundle\PaymentBundle\EventListener\Callback
 */
class PaymentCheckoutRedirectListener
{
    /** @var Session */
    private $session;

    /**
     * @var PaymentMethodProviderInterface
     */
    protected $paymentMethodProvider;

    /**
     * @var PaymentResultMessageProviderInterface
     */
    protected $messageProvider;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * PaymentCheckoutRedirectListener constructor.
     *
     * @param Session $session
     * @param PaymentMethodProviderInterface $paymentMethodProvider
     * @param PaymentResultMessageProviderInterface $messageProvider
     * @param DoctrineHelper $doctrineHelper
     * @param RouterInterface $router
     */
    public function __construct(
        Session $session,
        PaymentMethodProviderInterface $paymentMethodProvider,
        PaymentResultMessageProviderInterface $messageProvider,
        DoctrineHelper $doctrineHelper,
        RouterInterface $router
    ) {
        $this->session = $session;
        $this->paymentMethodProvider = $paymentMethodProvider;
        $this->messageProvider = $messageProvider;
        $this->doctrineHelper = $doctrineHelper;
        $this->router = $router;
    }

    /**
     * @param AbstractCallbackEvent $event
     */
    public function onReturn(AbstractCallbackEvent $event)
    {
        $paymentTransaction = $event->getPaymentTransaction();

        if (!$paymentTransaction) {
            return;
        }

        /** @var Order $order */
        $order = $this->doctrineHelper->getEntity(
            $paymentTransaction->getEntityClass(),
            $paymentTransaction->getEntityIdentifier()
        );

        if (!$order) {
            $paymentTransaction->setSuccessful(false);
            $this->redirectToFailureUrl(
                $paymentTransaction,
                $event,
                $this->router->generate('oro_frontend_root')
            );
            return;
        }

        if ($paymentTransaction->isSuccessful()) {
            return;
        }

        if (false === $this->paymentMethodProvider->hasPaymentMethod($paymentTransaction->getPaymentMethod())) {
            return;
        }

        if ($paymentTransaction->getPaymentMethod() === MolliePaymentConfigInterface::ADMIN_PAYMENT_LINK_ID) {
            return;
        }

        $this->redirectToFailureUrl($paymentTransaction, $event);
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @param AbstractCallbackEvent $event
     * @param string $forceRedirectUrl
     */
    private function redirectToFailureUrl(
        PaymentTransaction $paymentTransaction,
        AbstractCallbackEvent $event,
        $redirectUrl = ''
    ) {
        $event->stopPropagation();

        $transactionOptions = $paymentTransaction->getTransactionOptions();

        if (empty($redirectUrl) && !empty($transactionOptions['failureUrl'])) {
            $redirectUrl = $transactionOptions['failureUrl'];
        }

        if (!empty($redirectUrl)) {
            $event->setResponse(new RedirectResponse($redirectUrl));
        } else {
            $event->markFailed();
        }

        $flashBag = $this->session->getFlashBag();
        if (!$flashBag->has('error')) {
            $flashBag->add('error', $this->messageProvider->getErrorMessage($paymentTransaction));
        }
    }
}
