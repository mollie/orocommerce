<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\OrgToken;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\AuthorizationService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\Interfaces\TokenInterface;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\TokenPermission;

/**
 * Class OrgTokenAuthService
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\OrgToken
 */
class OrgTokenAuthService extends AuthorizationService
{
    private static $REQUIRED_TOKEN_PERMISSIONS = array(
        'customers.read', 'customers.write',
        'invoices.read',
        'onboarding.read', 'onboarding.write',
        'orders.read', 'orders.write',
        'organizations.read', 'organizations.write',
        'payments.read', 'payments.write',
        'profiles.read', 'profiles.write',
        'refunds.read', 'refunds.write',
        'settlements.read',
        'shipments.read', 'shipments.write',
        'subscriptions.read', 'subscriptions.write',
    );

    /**
     * Validates access token
     *
     * @param TokenInterface $token
     *
     * @return bool Validation result
     */
    public function validateToken(TokenInterface $token)
    {
        $configService = $this->getConfigService();
        $proxy = $this->getProxy();

        $tokenPermissions = $configService->doWithContext(
            'token_verification',
            function () use ($token, $configService, $proxy) {
                $configService->setAuthorizationToken($token->getToken());
                $configService->setTestMode($token->isTest());

                try {
                    $result = $proxy->getAccessTokenPermissions();
                } catch (\Exception $e) {
                    $result = array();
                }

                $configService->removeConfigValue('authToken');
                $configService->removeConfigValue('testMode');

                return $result;
            }
        );

        return $this->isTokenPermissionListValid($tokenPermissions);
    }

    /**
     * @param TokenPermission[] $tokenPermissions
     *
     * @return bool
     */
    protected function isTokenPermissionListValid($tokenPermissions)
    {
        if (empty($tokenPermissions)) {
            return false;
        }

        $grantTokenPermissionIds = array();
        foreach ($tokenPermissions as $tokenPermission) {
            if ($tokenPermission->isGranted()) {
                $grantTokenPermissionIds[] = $tokenPermission->getId();
            }
        }

        foreach (static::$REQUIRED_TOKEN_PERMISSIONS as $requiredPermissionId) {
            if (!in_array($requiredPermissionId, $grantTokenPermissionIds)) {
                return false;
            }
        }

        return true;
    }
}
