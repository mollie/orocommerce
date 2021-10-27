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

    public function testAmountValueFromCurrencySmallestUnit()
    {
        $amount = Amount::fromSmallestUnit(10147, 'KWD');

        $this->assertEquals(10.147, $amount->getAmountValue());

        $amountArray = $amount->toArray();

        $this->assertSame('10.147', $amountArray['value']);
    }

    public function testAmountValueWithoutMinorUnits()
    {
        $amount = Amount::fromSmallestUnit(10147, 'JPY');

        $this->assertEquals(10147, $amount->getAmountValue());

        $amountArray = $amount->toArray();

        $this->assertSame('10147', $amountArray['value']);
    }

    public function testAmountValueToCurrencySmallestUnit()
    {
        $amount = Amount::fromArray(array(
            'value' => 10.147,
            'currency' => 'KWD'
        ));

        $this->assertEquals(10147, $amount->getAmountValueInSmallestUnit());
    }

    public function testAmountConversionToCurrencySmallestUnit()
    {
        $amount = Amount::fromArray(array(
            'value' => 2.05,
            'currency' => 'EUR'
        ));

        $this->assertEquals(205, $amount->getAmountValueInSmallestUnit());
    }

    public function testExistingCurrency()
    {
        $amount = Amount::fromSmallestUnit(101475, 'UYW');

        $this->assertEquals('UYW', $amount->getCurrency());
        $this->assertEquals(10.1475, $amount->getAmountValue());

        $amountArray = $amount->toArray();

        $this->assertSame('10.1475', $amountArray['value']);
    }

    public function testNonExistentCurrency()
    {
        $amount = Amount::fromSmallestUnit(10147, 'TES');

        $this->assertEquals('TES', $amount->getCurrency());
        $this->assertEquals(101.47, $amount->getAmountValue());

        $amountArray = $amount->toArray();

        $this->assertSame('101.47', $amountArray['value']);
    }
}
