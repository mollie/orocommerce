<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Http\DTO;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Amount;
use PHPUnit\Framework\TestCase;

class AmountTest extends TestCase
{
    public function testAmountValueAwaysHaveTwoDecimals()
    {
        $integerAmount = Amount::fromArray(array(
            'value' => '123',
            'currency' => 'EUR'
        ));
        $oneDecimalAmount = new Amount();
        $oneDecimalAmount->setAmountValue('123.2');
        $fourDecimalAmount = Amount::fromArray(array(
            'value' => '123.4567',
            'currency' => 'EUR'
        ));

        $this->assertSame('123.00', $integerAmount->getAmountValue());
        $this->assertSame('123.20', $oneDecimalAmount->getAmountValue());
        $this->assertSame('123.46', $fourDecimalAmount->getAmountValue());
    }
}
