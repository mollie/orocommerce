<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Interfaces\OrderTransitionService;

class TestOrderTransitionService implements OrderTransitionService
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
    public function payOrder($orderId, array $metadata)
    {
        $this->callHistory['payOrder'][] = array('orderId' => $orderId, 'metadata' => $metadata);
    }

    /**
     * {@inheritdoc}
     */
    public function expireOrder($orderId, array $metadata)
    {
        $this->callHistory['expireOrder'][] = array('orderId' => $orderId, 'metadata' => $metadata);
    }

    /**
     * {@inheritdoc}
     */
    public function cancelOrder($orderId, array $metadata)
    {
        $this->callHistory['cancelOrder'][] = array('orderId' => $orderId, 'metadata' => $metadata);
    }

    /**
     * {@inheritdoc}
     */
    public function failOrder($orderId, array $metadata)
    {
        $this->callHistory['failOrder'][] = array('orderId' => $orderId, 'metadata' => $metadata);
    }

    /**
     * {@inheritdoc}
     */
    public function completeOrder($orderId, array $metadata)
    {
        $this->callHistory['completeOrder'][] = array('orderId' => $orderId, 'metadata' => $metadata);
    }

    /**
     * {@inheritdoc}
     */
    public function authorizeOrder($orderId, array $metadata)
    {
        $this->callHistory['authorizeOrder'][] = array('orderId' => $orderId, 'metadata' => $metadata);
    }

    /**
     * {@inheritdoc}
     */
    public function refundOrder($orderId, array $metadata)
    {
        $this->callHistory['refundOrder'][] = array('orderId' => $orderId, 'metadata' => $metadata);
    }
}
