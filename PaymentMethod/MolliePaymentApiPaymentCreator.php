<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Payments\PaymentService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpRequestException;
use Mollie\Bundle\PaymentBundle\Mapper\MollieDtoMapperInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

/**
 * Class MolliePaymentApiPaymentCreator
 *
 * @package Mollie\Bundle\PaymentBundle\PaymentMethod
 */
class MolliePaymentApiPaymentCreator implements MolliePaymentCreatorInterface
{
    /**
     * @var MollieDtoMapperInterface
     */
    private $mapper;
    /**
     * @var PaymentService
     */
    private $paymentService;

    /**
     * MolliePaymentApiPaymentCreator constructor.
     *
     * @param MollieDtoMapperInterface $mapper
     * @param PaymentService $paymentService
     */
    public function __construct(MollieDtoMapperInterface $mapper, PaymentService $paymentService)
    {
        $this->mapper = $mapper;
        $this->paymentService = $paymentService;
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
        $payment = $this->paymentService->createPayment(
            $paymentTransaction->getEntityIdentifier(),
            $this->mapper->getPaymentData($paymentTransaction)
        );

        return new MolliePaymentApiPaymentResult($payment);
    }
}
