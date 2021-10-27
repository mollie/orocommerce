<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Utility;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Utility\CurrencySymbolService;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\BaseTestWithServices;

class CurrencySymbolServiceTest extends BaseTestWithServices
{
    public function testValidCurrency()
    {
        self::assertEquals('€', CurrencySymbolService::getCurrencySymbol('EUR'));
        self::assertEquals('$', CurrencySymbolService::getCurrencySymbol('USD'));
        self::assertEquals('£', CurrencySymbolService::getCurrencySymbol('GBP'));
    }

    public function testInvalidCurrency()
    {
        self::assertEquals('test', CurrencySymbolService::getCurrencySymbol('test'));
        self::assertEquals('zzz', CurrencySymbolService::getCurrencySymbol('zzz'));
        self::assertEquals('', CurrencySymbolService::getCurrencySymbol(''));
    }
}
