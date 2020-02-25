<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Payments;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\BaseService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Payment;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Exceptions\ReferenceNotFoundException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\OrderReferenceService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;

/**
 * Class PaymentService
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Payments
 */
class PaymentService extends BaseService
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
     * Creates new payment on Mollie for a provided payment data
     *
     * @param string $shopReference Unique identifier of a shop order
     * @param Payment $payment payment data to pass to create method
     *
     * @return Payment Created payment instance
     *
     * @throws UnprocessableEntityRequestException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function createPayment($shopReference, Payment $payment)
    {
        $createdPayment = $this->getProxy()->createPayment($payment);

        /** @var OrderReferenceService $orderReferenceService */
        $orderReferenceService = ServiceRegister::getService(OrderReferenceService::CLASS_NAME);
        $orderReferenceService->updateOrderReference($createdPayment, $shopReference, PaymentMethodConfig::API_METHOD_PAYMENT);


        return $createdPayment;
    }

    /**
     * @param string $shopReference Unique identifier of a shop order
     *
     * @return Payment
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws ReferenceNotFoundException
     * @throws UnprocessableEntityRequestException
     */
    public function getPayment($shopReference)
    {
        /** @var OrderReferenceService $orderReferenceService */
        $orderReferenceService = ServiceRegister::getService(OrderReferenceService::CLASS_NAME);
        $orderReference = $orderReferenceService->getByShopReference($shopReference);
        if ($orderReference && $paymentId = $orderReference->getMollieReference()) {
            return $this->getProxy()->getPayment($paymentId);
        }

        throw new ReferenceNotFoundException("An error occurred when fetching payment: order reference not found. Shop reference: {$shopReference}");
    }
}
