<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\Interfaces\TokenInterface;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Connect\AuthorizationService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Connect\DTO\AuthInfo;

/**
 * Class TestAuthorizationService
 * @package Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents
 */
class MockAuthorizationService extends AuthorizationService
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getClientId()
    {
        return 'clientId';
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return 'https://test/tets';
    }

    /**
     * {@inheritdoc}
     *
     * @param TokenInterface $token
     */
    public function connect(TokenInterface $token)
    {
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getApplicationPermissions()
    {
        return array('payments.read', 'organizations.read');
    }

    /**
     * {@inheritdoc}
     *
     * @param TokenInterface $token
     *
     * @return bool
     */
    public function validateToken(TokenInterface $token)
    {
        return true;
    }

    /**
     * Returns valid AuthInfo object (accessToken is still valid)
     *
     * @return AuthInfo
     */
    public function getMockupAuthInfo()
    {
        $time = new \DateTime('now');

        return new AuthInfo('accessToken', 'refreshToken', $time->getTimestamp());
    }

    /**
     * Returns invalid AuthInfo object (accessToken is not valid)
     *
     * @return AuthInfo
     */
    public function getInvalidMockupAuthInfo()
    {
        $time = new \DateTime('2021-6-1');

        return new AuthInfo('accessToken', 'refreshToken', $time->getTimestamp());
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getClientSecret()
    {
        return 'clientSecret';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthToken()
    {
        return 'authToken';
    }
}
