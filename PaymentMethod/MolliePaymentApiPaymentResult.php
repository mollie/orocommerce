<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Payment;

class MolliePaymentApiPaymentResult implements MolliePaymentResultInterface
{
    /**
     * @var Payment
     */
    private $payment;

    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }
    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->payment->getId();
    }

    /**
     * @inheritDoc
     */
    public function getRedirectLink()
    {
        return $this->payment->getLink('checkout');
    }
}