<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\OrderLine;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Interfaces\OrderLineTransitionService;

class TestOrderLineTransitionService implements OrderLineTransitionService
{

    /**
     * History of method calls for testing purposes.
     *
     * @var array
     */
    private $callHistory = array();

    public function getCallHistory($method = '')
    {
        if (empty($method)) {
            return $this->callHistory;
        }

        return array_key_exists($method, $this->callHistory) ? $this->callHistory[$method] : array();
    }

    /**
     * {@inheritdoc}
     */
    public function payOrderLine($orderId, OrderLine $orderLine)
    {
        $this->callHistory['payOrderLine'][] = array('orderId' => $orderId, 'orderLine' => $orderLine);
    }

    /**
     * {@inheritdoc}
     */
    public function cancelOrderLine($orderId, OrderLine $orderLine)
    {
        $this->callHistory['cancelOrderLine'][] = array('orderId' => $orderId, 'orderLine' => $orderLine);
    }

    /**
     * {@inheritdoc}
     */
    public function authorizeOrderLine($orderId, OrderLine $orderLine)
    {
        $this->callHistory['authorizeOrderLine'][] = array('orderId' => $orderId, 'orderLine' => $orderLine);
    }

    /**
     * {@inheritdoc}
     */
    public function completeOrderLine($orderId, OrderLine $orderLine)
    {
        $this->callHistory['completeOrderLine'][] = array('orderId' => $orderId, 'orderLine' => $orderLine);
    }

    public function refundOrderLine($orderId, OrderLine $orderLine)
    {
        $this->callHistory['refundOrderLine'][] = array('orderId' => $orderId, 'orderLine' => $orderLine);
    }
}
