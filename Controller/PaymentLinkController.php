<?php

namespace Mollie\Bundle\PaymentBundle\Controller;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\CheckoutLink\CheckoutLinkService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Configuration\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\Logger;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfigInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\EventListener\Callback\RedirectListener;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class PaymentLinkController
 *
 * @package Mollie\Bundle\PaymentBundle\Controller
 */
class PaymentLinkController extends AbstractController
{
    /**
     * @Route("/paymentlink/generate/{orderId}", name="mollie_payment_link", methods={"GET"})
     * @ParamConverter("order", class="OroOrderBundle:Order", options={"id" = "orderId"})
     * @param Order $order
     * @AclAncestor("oro_order_frontend_view")
     *
     * @return JsonResponse|RedirectResponse
     * @throws \Throwable
     */
    public function goToMolliePaymentPage(Order $order)
    {
        try {
            $paymentUtility = $this->get('mollie_payment.manager.oro_payment_method_utility');

            $paymentKey = $paymentUtility->getPaymentKey($order);
            $isMolliePayment = $paymentUtility->hasMolliePaymentConfig($order);

            if (!empty($paymentKey) && !$isMolliePayment) {
                $this->deactivateAllTransactions($order);
            }

            $this->createAndExecutePaymentLinkTransaction($order);

            /** @var Configuration $configService */
            $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
            return $configService->doWithContext($paymentUtility->getChannelId($order), function () use ($order) {
                /** @var CheckoutLinkService $checkoutLinkService */
                $checkoutLinkService = ServiceRegister::getService(CheckoutLinkService::CLASS_NAME);
                $link = $checkoutLinkService->getCheckoutLink($order->getIdentifier());

                return new RedirectResponse($link->getHref());
            });

        } catch (\Exception $exception) {
            Logger::logError(
                'Admin payment link failed',
                'Integration',
                [
                    'ExceptionMessage' => $exception->getMessage(),
                    'ExceptionTrace' => $exception->getTraceAsString(),
                ]
            );
            return new RedirectResponse($this->generateOrderViewLink($order));
        }
    }

    /**
     * @param Order $order
     * @throws \Throwable
     */
    private function deactivateAllTransactions(Order $order)
    {
        $transactionProvider = $this->get('oro_payment.provider.payment_transaction');
        $activeTransactions = $transactionProvider->getPaymentTransactions($order, ['active' => true]);
        foreach ($activeTransactions as $activeTransaction) {
            $activeTransaction->setActive(false);
            $transactionProvider->savePaymentTransaction($activeTransaction);
        }
    }

    /**
     * @param Order $order
     * @throws \Throwable
     */
    private function createAndExecutePaymentLinkTransaction(Order $order)
    {
        if (!$this->shouldRecreatePaymentLinkTransaction($order)) {
            return;
        }

        $transactionProvider = $this->get('oro_payment.provider.payment_transaction');

        $paymentTransaction = $transactionProvider->createPaymentTransaction(
            MolliePaymentConfigInterface::ADMIN_PAYMENT_LINK_ID,
            PaymentMethodInterface::PURCHASE,
            $order
        );
        $paymentTransaction
            ->setAmount($order->getTotal())
            ->setCurrency($order->getCurrency())
            ->setTransactionOptions([
                RedirectListener::SUCCESS_URL_KEY => $this->generateOrderViewLink($order),
                RedirectListener::FAILURE_URL_KEY => $this->generateOrderViewLink($order),
            ]);

        $this->executePaymentTransaction($paymentTransaction);

        $transactionProvider->savePaymentTransaction($paymentTransaction);
    }

    /**
     * @param Order $order
     * @return bool
     */
    private function shouldRecreatePaymentLinkTransaction(Order $order)
    {
        $paymentUtility = $this->get('mollie_payment.manager.oro_payment_method_utility');
        $isMolliePayment = $paymentUtility->hasMolliePaymentConfig($order);

        if (!$isMolliePayment) {
            return true;
        }

        $transactionProvider = $this->get('oro_payment.provider.payment_transaction');
        $lastMollieTransaction = $transactionProvider->getPaymentTransaction($order);
        if (!$lastMollieTransaction) {
            return true;
        }

        // Only recreate admin payment link transactions to refresh lis of available payment methods selected by admin
        if ($lastMollieTransaction->getPaymentMethod() !== MolliePaymentConfigInterface::ADMIN_PAYMENT_LINK_ID) {
            return false;
        }

        return !$lastMollieTransaction->isSuccessful() && !$lastMollieTransaction->isActive();
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    private function executePaymentTransaction(PaymentTransaction $paymentTransaction)
    {
        try {
            $paymentMethodProvider = $this->get('oro_payment.payment_method.composite_provider');
            $paymentMethodIdentifier = $paymentTransaction->getPaymentMethod();
            if ($paymentMethodProvider->hasPaymentMethod($paymentMethodIdentifier)) {
                return $paymentMethodProvider
                    ->getPaymentMethod($paymentMethodIdentifier)
                    ->execute($paymentTransaction->getAction(), $paymentTransaction);
            }
        } catch (\Exception $e) {
            Logger::logError(
                'Admin payment link execution failed',
                'Integration',
                [
                    'ExceptionMessage' => $e->getMessage(),
                    'ExceptionTrace' => $e->getTraceAsString(),
                ]
            );
        }

        return [];
    }

    /**
     * @param Order $order
     * @return string
     */
    private function generateOrderViewLink(Order $order)
    {
        return $this->generateUrl(
            'oro_order_frontend_view',
            ['id' => $order->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }
}
