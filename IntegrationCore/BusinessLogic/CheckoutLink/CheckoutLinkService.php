<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\CheckoutLink;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\BaseService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\CheckoutLink\Exceptions\CheckoutLinkNotAvailableException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Link;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Order;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Payment;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Model\OrderReference;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\OrderReferenceService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\OrderService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Payments\PaymentService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;

/**
 * Class CheckoutLinkService
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\CheckoutLink
 */
class CheckoutLinkService extends BaseService
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;

    /**
     * @param string $shopReference
     *
     * @return Link
     *
     * @throws UnprocessableEntityRequestException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws CheckoutLinkNotAvailableException
     */
    public function getCheckoutLink($shopReference)
    {
        /** @var OrderReferenceService $orderReferenceService */
        $orderReferenceService = ServiceRegister::getService(OrderReferenceService::CLASS_NAME);
        $orderReference = $orderReferenceService->getByShopReference($shopReference);
        if (!$orderReference) {
            throw new CheckoutLinkNotAvailableException("Reference not found for order: {$shopReference}");
        }

        if ($orderReference->getApiMethod() === PaymentMethodConfig::API_METHOD_PAYMENT) {
            return $this->getPaymentCheckoutLink($orderReference);
        }

        return $this->getOrderCheckoutLink($orderReference);
    }

    /**
     * @param OrderReference $orderReference
     *
     * @return Link|null
     * @throws CheckoutLinkNotAvailableException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     */
    protected function getPaymentCheckoutLink(OrderReference $orderReference)
    {
        $payment = $this->getProxy()->getPayment($orderReference->getMollieReference());
        if ($payment->getStatus() === 'paid') {
            throw new CheckoutLinkNotAvailableException('Payment is in terminal state');
        }

        if ($link = $payment->getLink('checkout')) {
            return $link;
        }

        if ($link = $payment->getLink('changePaymentState')) {
            return $link;
        }

        return $this->createPaymentAndGetCheckoutLink($orderReference->getShopReference(), $payment);
    }

    /**
     * @param string $shopReference
     * @param Payment $payment
     *
     * @return Link|null
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     */
    protected function createPaymentAndGetCheckoutLink($shopReference, Payment $payment)
    {
        /** @var PaymentService $paymentService */
        $paymentService = ServiceRegister::getService(PaymentService::CLASS_NAME);
        $createdPayment = $paymentService->createPayment($shopReference, $payment);

        $link = $createdPayment->getLink('checkout');
        $link = $link ?: $createdPayment->getLink('changePaymentState');

        return $link;
    }

    /**
     * @param OrderReference $orderReference
     *
     * @return Link|null
     * @throws CheckoutLinkNotAvailableException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     */
    protected function getOrderCheckoutLink(OrderReference $orderReference)
    {
        $order = $this->getProxy()->getOrder($orderReference->getMollieReference());
        if ($link = $order->getLink('checkout')) {
            return $link;
        }

        if ($order->getStatus() === 'expired') {
            return $this->createOrderAndGetCheckoutLink($orderReference->getShopReference(), $order);
        }

        throw new CheckoutLinkNotAvailableException('Order is in terminal state');
    }

    /**
     * @param string $shopReference
     * @param Order $order
     *
     * @return Link|null
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     */
    protected function createOrderAndGetCheckoutLink($shopReference, Order $order)
    {
        $this->skipCancelledItems($order);
        /** @var OrderService $orderService */
        $orderService = ServiceRegister::getService(OrderService::CLASS_NAME);
        $createdOrder = $orderService->createOrder($shopReference, $order);

        return $createdOrder->getLink('checkout');
    }

    /**
     * @param Order $order
     */
    protected function skipCancelledItems(Order $order)
    {
        $nonCanceledLines = array();
        foreach ($order->getLines() as $line) {
            if ($line->getStatus() !== 'canceled') {
                $nonCanceledLines[] = $line;
            }
        }

        $order->setLines($nonCanceledLines);
    }
}
