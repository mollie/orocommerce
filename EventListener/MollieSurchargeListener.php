<?php

namespace Mollie\Bundle\PaymentBundle\EventListener;

use Oro\Bundle\PaymentBundle\EventListener\AbstractSurchargeListener;
use Oro\Bundle\PaymentBundle\Model\Surcharge;

class MollieSurchargeListener extends AbstractSurchargeListener
{
    /**
     * {@inheritdoc}
     */
    protected function setAmount(Surcharge $model, $amount)
    {
        $model->setHandlingAmount($model->getHandlingAmount() + $amount);
    }
}