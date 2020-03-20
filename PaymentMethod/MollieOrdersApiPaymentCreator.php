<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\OrderService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;
use Mollie\Bundle\PaymentBundle\Mapper\MollieDtoMapperInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

class MollieOrdersApiPaymentCreator implements MolliePaymentCreatorInterface
{
    /**
     * @var MollieDtoMapperInterface
     */
    private $mapper;

    /**
     * MollieOrdersApiPaymentCreator constructor.
     *
     * @param MollieDtoMapperInterface $mapper
     */
    public function __construct(MollieDtoMapperInterface $mapper)
    {
        $this->mapper = $mapper;
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

        /** @var OrderService $orderService */
        $orderService = ServiceRegister::getService(OrderService::CLASS_NAME);
        $order = $orderService->createOrder(
            $paymentTransaction->getEntityIdentifier(),
            $orderData
        );

        return new MollieOrdersApiPaymentResult($order);
    }
}
