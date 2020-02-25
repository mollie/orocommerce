<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Refunds;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\BaseService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Amount;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Order;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\OrderLine;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Payment;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Refunds\Refund;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Exceptions\ReferenceNotFoundException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Model\OrderReference;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\OrderReferenceService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\OrderService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Refunds\Exceptions\RefundNotAllowedException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;

/**
 * Class RefundService
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Refunds
 */
class RefundService extends BaseService
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
     * Refunds payment
     *
     * @param string|int $shopReference unique identifier of shop order
     * @param Refund $refund Refund object
     *
     * @return Refund|null
     *
     * @throws UnprocessableEntityRequestException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws ReferenceNotFoundException
     */
    public function refundPayment($shopReference, Refund $refund)
    {
        if ($orderReference = $this->getOrderReference($shopReference)) {
            return $this->getProxy()->createPaymentRefund($refund, $orderReference->getMollieReference());
        }

        throw new ReferenceNotFoundException("An error during payment refund occurred: order reference not found. Shop reference: {$shopReference}");
    }

    /**
     * Refunds order lines
     *
     * @param string|int $shopReference unique identifier of shop order
     * @param Refund $refund Refund object
     *
     * @return Refund|null
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     * @throws ReferenceNotFoundException
     */
    public function refundOrderLines($shopReference, Refund $refund)
    {
        /** @var OrderService $orderService */
        $orderService = ServiceRegister::getService(OrderService::CLASS_NAME);
        if ($existingOrder = $orderService->getOrder($shopReference)) {
            if ($this->isFullRefund($existingOrder, $refund)) {
                $refund->setLines(array());
            }

            return $this->getProxy()->createOrderLinesRefund($refund, $existingOrder->getId());
        }

        throw new ReferenceNotFoundException("An error during order line refund occurred: order reference not found. Shop reference: {$shopReference}");
    }

    /**
     * Refunds order
     *
     * @param string|int $shopReference unique identifier of shop order
     * @param Refund $refund Refund object
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws RefundNotAllowedException
     * @throws UnprocessableEntityRequestException
     * @throws ReferenceNotFoundException
     */
    public function refundWholeOrder($shopReference, Refund $refund)
    {
        $order = OrderService::getInstance()->getOrder($shopReference);
        if (!$order->getId()) {
            return;
        }

        $refundablePayments = $this->getRefundablePayments($order);
        $amountValueToRefund = (float)$refund->getAmount()->getAmountValue();
        $this->checkIfRefundIsPossible($amountValueToRefund, $order);
        $index = 0;
        while ($amountValueToRefund > 0) {
            /** @var Payment $existingPayment */
            $existingPayment = $refundablePayments[$index];
            $paymentForRefund = new Refund();
            $amountToRefund = $this->getAmountForRefund($amountValueToRefund, $existingPayment->getAmount());
            $paymentForRefund->setAmount($amountToRefund);
            $paymentForRefund->setDescription($refund->getDescription());

            $amountValueToRefund -= ((float)$amountToRefund->getAmountValue());
            $index++;

            $this->getProxy()->createPaymentRefund($paymentForRefund, $existingPayment->getId());
        }
    }

    /**
     * Check if refund all remaining items
     *
     * @param Order $existingOrder
     * @param Refund $refund
     *
     * @return bool
     */
    private function isFullRefund(Order $existingOrder, Refund $refund)
    {
        $refundLinesMap = $this->createRefundLinesMap($refund->getLines());
        foreach ($existingOrder->getLines() as $existingLine) {
            if (!array_key_exists($existingLine->getId(), $refundLinesMap)) {
                if ($this->skipItem($existingLine, $refundLinesMap)) {
                    continue;
                }

                return false;
            }

            /** @var OrderLine $refundLine */
            $refundLine = $refundLinesMap[$existingLine->getId()];
            if ($existingLine->getRefundableQuantity() !== $refundLine->getQuantity()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param OrderLine $orderLine
     * @param array $refundLinesMap
     *
     * @return bool
     */
    private function skipItem(OrderLine $orderLine, array $refundLinesMap)
    {
        return ($orderLine->getType() === 'discount') ||
            (!array_key_exists($orderLine->getId(), $refundLinesMap) && $orderLine->getRefundableQuantity() === 0);
    }

    /**
     * @param OrderLine[] $refundLines
     *
     * @return array
     */
    private function createRefundLinesMap(array $refundLines)
    {
        $map = array();
        foreach ($refundLines as $refundLine) {
            $map[$refundLine->getId()] = $refundLine;
        }

        return $map;
    }

    /**
     * Returns amount to refund. If amount value
     *
     * @param $amountValueToRefund
     * @param Amount $existingAmount
     *
     * @return Amount
     */
    private function getAmountForRefund($amountValueToRefund, Amount $existingAmount)
    {
        $amount = new Amount();
        $amount->setCurrency($existingAmount->getCurrency());
        $amount->setAmountValue(min($amountValueToRefund, $existingAmount->getAmountValue()));

        return $amount;
    }

    /**
     * Returns OrderReference entity by shop order id
     *
     * @param $shopReference
     *
     * @return OrderReference|null
     */
    private function getOrderReference($shopReference)
    {
        /** @var OrderReferenceService $orderReferenceService */
        $orderReferenceService = ServiceRegister::getService(OrderReferenceService::CLASS_NAME);

        return $orderReferenceService->getByShopReference($shopReference);
    }

    /**
     * Returns payments that can be refunded
     *
     * @param Order $order
     *
     * @return array
     * @throws RefundNotAllowedException
     */
    private function getRefundablePayments(Order $order)
    {
        $embedded = $order->getEmbedded();
        $payments = $embedded['payments'];
        $refundablePayments = array();
        /** @var Payment $payment */
        foreach ($payments as $payment) {
            if (in_array($payment->getStatus(), array('authorized', 'paid'))) {
                $refundablePayments[] = $payment;
            }
        }

        if (empty($refundablePayments)) {
            throw new RefundNotAllowedException('There are no refundable payments');
        }

        return $refundablePayments;
    }

    /**
     * Check if amount to refund is bigger than the refundable amount
     *
     * @param float $amountToRefund
     * @param Order $order
     *
     * @throws RefundNotAllowedException
     */
    private function checkIfRefundIsPossible($amountToRefund, Order $order)
    {
        $refundableAmount = $order->getAmount()->getAmountValue() - $order->getAmountRefunded()->getAmountValue();
        if ($amountToRefund > $refundableAmount) {
            throw new RefundNotAllowedException('Amount to refund is bigger than the refundable amount');
        }
    }
}
