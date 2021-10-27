<?php


namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\PaymentTransactionDescriptionService;

class TestPaymentTransactionDescriptionService extends PaymentTransactionDescriptionService
{
    public $mockDescription;

    /**
     * {@inheritdoc}
     */
    protected function getDescription($methodIdentifier)
    {
        return $this->mockDescription;
    }
}
