<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Connect\DTO;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Connect\DTO\TokenRequest;
use PHPUnit\Framework\TestCase;

/**
 * Class TokenRequestTest
 * @package Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Connect\DTO
 */
class TokenRequestTest extends TestCase
{
    public function testTestRequestToArray()
    {
        $arrayCode = array(
            'grant_type' => 'authorization_code',
            'code' => 'testCode',
            'redirect_uri' => 'someUrl',
        );

        $arrayRefresh = array(
            'grant_type' => 'refresh_token',
            'refresh_token' => 'testRefresh',
        );

        $tokenCode = new TokenRequest('authorization_code', 'testCode', '', 'someUrl');
        $tokenRefresh = new TokenRequest('refresh_token', '', 'testRefresh', '');

        self::assertSame($arrayCode, $tokenCode->toArray());
        self::assertSame($arrayRefresh, $tokenRefresh->toArray());
    }
}
