<?php

namespace Mollie\Bundle\PaymentBundle\Condition;

use Mollie\Bundle\PaymentBundle\Form\Entity\MollieRefund;
use Mollie\Bundle\PaymentBundle\Manager\MollieRefundProvider;
use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ExpressionInterface;

/**
 * Class RefundFormValidRules
 *
 * @package Mollie\Bundle\PaymentBundle\Condition
 */
class RefundFormValid extends AbstractCondition
{
    const NAME = 'mollie_refund_form_valid';

    /**
     * @return string
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * @param array $options
     * @return $this|ExpressionInterface
     */
    public function initialize(array $options)
    {
        reset($options);

        return $this;
    }

    /**
     * Validates input
     *
     * @param mixed $context
     *
     * @return bool
     */
    protected function isConditionAllowed($context)
    {
        /** @var MollieRefund $mollieRefund */
        $mollieRefund = $context->get('mollieRefund');
        if (!$mollieRefund) {
            return false;
        }

        if (!is_numeric($mollieRefund->getRefundPayment()->getAmount())) {
            return false;
        }

        return true;
    }
}
