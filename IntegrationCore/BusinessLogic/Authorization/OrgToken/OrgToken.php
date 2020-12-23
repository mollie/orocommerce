<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\OrgToken;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\Interfaces\TokenInterface;

/**
 * Class OrgToken
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\OrgToken
 */
class OrgToken implements TokenInterface
{
    /**
     * @var string
     */
    private $orgToken;
    /**
     * @var bool
     */
    private $isTest;

    /**
     * OrgToken constructor.
     *
     * @param string $orgToken
     * @param bool $isTest
     */
    public function __construct($orgToken, $isTest)
    {
        $this->orgToken = $orgToken;
        $this->isTest = $isTest;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->orgToken;
    }

    /**
     * @return bool
     */
    public function isTest()
    {
        return $this->isTest;
    }
}
