<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Connect\DTO;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Connect\DTO\AuthInfo;
use PHPUnit\Framework\TestCase;

/**
 * Class AuthInfoTest
 * @package Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Connect\DTO
 */
class AuthInfoTest extends TestCase
{
    /**
     * AuthInfo toArray() test function
     */
    public function testAuthInfoToArray()
    {
        $array = array(
            'access_token' => 'testAccess',
            'refresh_token' => 'testRefresh',
            'expires_in' => 542689,
        );

        $authInfo = new AuthInfo('testAccess', 'testRefresh', 542689);

        self::assertSame($array, $authInfo->toArray());
    }

    /**
     * Tests Auth info fromArray function
     */
    public function testAuthInfoFromArray()
    {
        $array = array(
            'access_token' => 'testAccess',
            'refresh_token' => 'testRefresh',
            'expires_in' => 542689,
        );

        $authInfo = new AuthInfo('testAccess', 'testRefresh', 542689);
        $authInfoTest = AuthInfo::fromArray($array);

        self::assertEquals($authInfoTest, $authInfo);
    }
}
