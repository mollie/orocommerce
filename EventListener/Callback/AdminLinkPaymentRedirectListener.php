<?php

namespace Mollie\Bundle\PaymentBundle\EventListener\Callback;

use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfigInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\AbstractCallbackEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class AdminLinkPaymentRedirectListener
 *
 * @package Mollie\Bundle\PaymentBundle\EventListener\Callback
 */
class AdminLinkPaymentRedirectListener
{
    /**
     * @var RequestStack
     */
    protected RequestStack $requestStack;
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * AdminLinkPaymentRedirectListener constructor.
     *
     * @param RequestStack $requestStack
     * @param RouterInterface $router
     * @param TranslatorInterface $translator
     */
    public function __construct(
        RequestStack $requestStack,
        RouterInterface $router,
        TranslatorInterface $translator
    ) {
        $this->requestStack = $requestStack;
        $this->router = $router;
        $this->translator = $translator;
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

        if ($paymentTransaction->getPaymentMethod() !== MolliePaymentConfigInterface::ADMIN_PAYMENT_LINK_ID) {
            return;
        }

        if ($paymentTransaction->isSuccessful()) {
            $this->requestStack->getSession()?->getFlashBag()->add('success', 'oro.checkout.workflow.success.thank_you.label');
            $event->markSuccessful();
            return;
        }


        $this->redirectToFailureUrl($paymentTransaction, $event);
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @param AbstractCallbackEvent $event
     */
    private function redirectToFailureUrl(
        PaymentTransaction $paymentTransaction,
        AbstractCallbackEvent $event
    ) {
        $event->stopPropagation();

        $transactionOptions = $paymentTransaction->getTransactionOptions();
        if (!empty($transactionOptions['failureUrl'])) {
            $event->setResponse(new RedirectResponse($transactionOptions['failureUrl']));
        } else {
            $event->markFailed();
        }

        $adminPaymentLink = $this->router->generate(
            'mollie_payment_link',
            ['orderId' => $paymentTransaction->getEntityIdentifier()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $this->session->getFlashBag()->add(
            'error',
            $this->translator->trans(
                'mollie.payment.checkout.admin_link_error',
                ['{adminPaymentLink}' => $adminPaymentLink]
            )
        );
    }
}
