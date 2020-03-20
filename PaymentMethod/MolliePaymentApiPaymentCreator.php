<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Payments\PaymentService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;
use Mollie\Bundle\PaymentBundle\Mapper\MollieDtoMapperInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

class MolliePaymentApiPaymentCreator implements MolliePaymentCreatorInterface
{
    /**
     * @var MollieDtoMapperInterface
     */
    private $mapper;

    /**
     * MolliePaymentApiPaymentCreator constructor.
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
        /** @var PaymentService $paymentService */
        $paymentService = ServiceRegister::getService(PaymentService::CLASS_NAME);
        $payment = $paymentService->createPayment(
            $paymentTransaction->getEntityIdentifier(),
            $this->mapper->getPaymentData($paymentTransaction)
        );

        return new MolliePaymentApiPaymentResult($payment);
    }
}