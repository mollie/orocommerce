<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\OrderService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpRequestException;
use Mollie\Bundle\PaymentBundle\Mapper\MollieDtoMapperInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

/**
 * Class MollieOrdersApiPaymentCreator
 *
 * @package Mollie\Bundle\PaymentBundle\PaymentMethod
 */
class MollieOrdersApiPaymentCreator implements MolliePaymentCreatorInterface
{
    /**
     * @var MollieDtoMapperInterface
     */
    private $mapper;
    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * MollieOrdersApiPaymentCreator constructor.
     *
     * @param MollieDtoMapperInterface $mapper
     * @param OrderService $orderService
     */
    public function __construct(MollieDtoMapperInterface $mapper, OrderService $orderService)
    {
        $this->mapper = $mapper;
        $this->orderService = $orderService;
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     *
     * @return MolliePaymentResultInterface|null
     *
     * @throws UnprocessableEntityRequestException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function createMolliePayment(PaymentTransaction $paymentTransaction)
    {
        $orderData = $this->mapper->getOrderData($paymentTransaction);
        if (!$orderData) {
            return null;
        }

        $order = $this->orderService->createOrder(
            $paymentTransaction->getEntityIdentifier(),
            $orderData
        );

        return new MollieOrdersApiPaymentResult($order);
    }
}
