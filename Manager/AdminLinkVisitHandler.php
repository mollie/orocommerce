<?php


namespace Mollie\Bundle\PaymentBundle\Manager;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\CheckoutLink\CheckoutLinkService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Configuration\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\Logger;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfigInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\EventListener\Callback\RedirectListener;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\CompositePaymentMethodProvider;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class AdminLinkVisitHandler
 *
 * @package Mollie\Bundle\PaymentBundle\Manager
 */
class AdminLinkVisitHandler
{
    /**
     * @var Configuration
     */
    private $configService;
    /**
     * @var CheckoutLinkService
     */
    private $checkoutLinkService;
    /**
     * @var OroPaymentMethodUtility
     */
    private $paymentUtility;
    /**
     * @var PaymentTransactionProvider
     */
    private $transactionProvider;
    /**
     * @var CompositePaymentMethodProvider
     */
    private $compositeProvider;
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * AdminLinkVisitHandler constructor.
     *
     * @param Configuration $configService
     * @param CheckoutLinkService $checkoutLinkService
     * @param OroPaymentMethodUtility $paymentUtility
     * @param PaymentTransactionProvider $transactionProvider
     * @param CompositePaymentMethodProvider $compositeProvider
     * @param RouterInterface $router
     */
    public function __construct(
        Configuration $configService,
        CheckoutLinkService $checkoutLinkService,
        OroPaymentMethodUtility $paymentUtility,
        PaymentTransactionProvider $transactionProvider,
        CompositePaymentMethodProvider $compositeProvider,
        RouterInterface $router
    ) {
        $this->configService = $configService;
        $this->checkoutLinkService = $checkoutLinkService;
        $this->paymentUtility = $paymentUtility;
        $this->transactionProvider = $transactionProvider;
        $this->compositeProvider = $compositeProvider;
        $this->router = $router;
    }

    /**
     * @param Order $order
     *
     * @return mixed
     * @throws \Throwable
     */
    public function handleAndGetCheckoutUrl(Order $order)
    {
        $paymentKey = $this->paymentUtility->getPaymentKey($order);
        $isMolliePayment = $this->paymentUtility->hasMolliePaymentConfig($order);
        if (!empty($paymentKey) && !$isMolliePayment) {
            $this->deactivateAllTransactions($order);
        }

        $this->createAndExecutePaymentLinkTransaction($order);

        return $this->configService->doWithContext($this->paymentUtility->getChannelId($order), function () use ($order) {
            return $this->checkoutLinkService->getCheckoutLink($order->getIdentifier())->getHref();
        });
    }

    /**
     * @param Order $order
     * @return string
     */
    public function generateOrderViewLink(Order $order)
    {
        return $this->router->generate(
            'oro_order_frontend_view',
            ['id' => $order->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * @param Order $order
     * @throws \Throwable
     */
    private function deactivateAllTransactions(Order $order)
    {
        $activeTransactions = $this->transactionProvider->getPaymentTransactions($order, ['active' => true]);
        foreach ($activeTransactions as $activeTransaction) {
            $activeTransaction->setActive(false);
            $this->transactionProvider->savePaymentTransaction($activeTransaction);
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

        $paymentTransaction = $this->transactionProvider->createPaymentTransaction(
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

        $this->transactionProvider->savePaymentTransaction($paymentTransaction);
    }

    /**
     * @param Order $order
     * @return bool
     */
    private function shouldRecreatePaymentLinkTransaction(Order $order)
    {
        $isMolliePayment = $this->paymentUtility->hasMolliePaymentConfig($order);

        if (!$isMolliePayment) {
            return true;
        }

        $lastMollieTransaction = $this->transactionProvider->getPaymentTransaction($order);
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
            $paymentMethodIdentifier = $paymentTransaction->getPaymentMethod();
            if ($this->compositeProvider->hasPaymentMethod($paymentMethodIdentifier)) {
                return $this->compositeProvider
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
}
