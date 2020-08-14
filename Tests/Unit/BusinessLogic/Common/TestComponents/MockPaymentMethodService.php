<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\PaymentMethodService;

class MockPaymentMethodService extends PaymentMethodService
{
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;

    /**
     * History of method calls for testing purposes.
     *
     * @var array
     */
    private $callHistory = array();

    /**
     * @var array|PaymentMethodConfig[]
     */
    private $paymentMethodConfigs;

    /**
     * @param PaymentMethodConfig[] $paymentMethodConfigs
     */
    public function setUp(array $paymentMethodConfigs)
    {
        $this->paymentMethodConfigs = $paymentMethodConfigs;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllPaymentMethodConfigurations($profileId)
    {
        $this->callHistory['getAllPaymentMethodConfigurations'][] = array('profileId' => $profileId);

        return $this->paymentMethodConfigs;
    }

    /**
     * {@inheritdoc}
     */
    public function getEnabledPaymentMethodConfigurations(
        $profileId,
        $billingCountry = null,
        $amount = null,
        $apiMethod = PaymentMethodConfig::API_METHOD_ORDERS
    ) {
        $this->callHistory['getEnabledPaymentMethodConfigurations'][] = array(
            'profileId' => $profileId,
            'billingCountry' => $billingCountry,
            'amount' => $amount,
            'apiMethod' => $apiMethod,
        );

        return $this->paymentMethodConfigs;
    }

    /**
     * {@inheritdoc}
     */
    public function clear($profileId)
    {
        $this->callHistory['clear'][] = array('profileId' => $profileId);
    }

    /**
     * {@inheritdoc}
     */
    public function clearAllOther($profileId)
    {
        $this->callHistory['clearAllOther'][] = array('profileId' => $profileId);
    }

    /**
     * Gets method call history for mock payment service
     *
     * @param string $method If not empty, only call history of a provided method will be returned
     *
     * @return array
     */
    public function getCallHistory($method = '')
    {
        if (empty($method)) {
            return $this->callHistory;
        }

        return array_key_exists($method, $this->callHistory) ? $this->callHistory[$method] : array();
    }
}
