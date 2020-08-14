<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Payment;

/**
 * Class MolliePaymentApiPaymentResult
 *
 * @package Mollie\Bundle\PaymentBundle\PaymentMethod
 */
class MolliePaymentApiPaymentResult implements MolliePaymentResultInterface
{
    /**
     * @var Payment
     */
    private $payment;

    /**
     * MolliePaymentApiPaymentResult constructor.
     *
     * @param Payment $payment
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->payment->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function getRedirectLink()
    {
        return $this->payment->getLink('checkout');
    }
}
