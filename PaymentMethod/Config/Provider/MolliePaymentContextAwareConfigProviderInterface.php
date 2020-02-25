<?php

namespace Mollie\Bundle\PaymentBundle\PaymentMethod\Config\Provider;


use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

interface MolliePaymentContextAwareConfigProviderInterface extends MolliePaymentConfigProviderInterface
{
    public function setPaymentContext(PaymentContextInterface $context);
}