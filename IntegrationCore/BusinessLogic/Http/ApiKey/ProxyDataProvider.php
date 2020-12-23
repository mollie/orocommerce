<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\ApiKey;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Order;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Payment;

/**
 * Class ProxyDataProvider
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\ApiKey
 */
class ProxyDataProvider extends \Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\OrgToken\ProxyDataProvider
{
    protected static $profileIdRequiredEndpoints = array();

    /**
     * @param Payment $payment
     *
     * @return array
     */
    public function transformPayment(Payment $payment)
    {
        $data = parent::transformPayment($payment);
        unset($data['profileId']);

        return $data;
    }

    /**
     * @param Order $order
     *
     * @return array
     */
    public function transformOrder(Order $order)
    {
        $data = parent::transformOrder($order);
        unset($data['profileId']);

        return $data;
    }

    /**
     * @param string $endpoint
     *
     * @return bool
     */
    protected function attachTestModeParameter($endpoint)
    {
        return false;
    }
}
