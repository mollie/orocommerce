<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\UI\Controllers;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\TokenPermission;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Proxy;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\PaymentMethodService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;

/**
 * Class AuthorizationController
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\UI\Controllers
 */
class AuthorizationController
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
     * @param string $token
     * @param bool $testMode
     *
     * @return bool Validation result
     */
    public function validateToken($token, $testMode)
    {
        /** @var Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        /** @var Proxy $proxy */
        $proxy = ServiceRegister::getService(Proxy::CLASS_NAME);

        $tokenPermissions = $configService->doWithContext(
            'token_verification',
            function () use ($token, $testMode, $configService, $proxy) {
                $configService->setAuthorizationToken($token);
                $configService->setTestMode($testMode);

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
     * Resets account
     */
    public function reset()
    {
        /** @var Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        /** @var PaymentMethodService $paymentMethodService */
        $paymentMethodService = ServiceRegister::getService(PaymentMethodService::CLASS_NAME);
        $websiteProfile = $configService->getWebsiteProfile();

        $configService->removeConfigValue('authToken');
        $configService->removeConfigValue('testMode');
        $configService->removeConfigValue('websiteProfile');
        if ($websiteProfile) {
            $paymentMethodService->clear($websiteProfile->getId());
        }
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
