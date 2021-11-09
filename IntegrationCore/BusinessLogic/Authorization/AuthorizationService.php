<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\Interfaces\TokenInterface;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\BaseService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\PaymentMethodService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;

/**
 * Class AuthorizationService
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization
 */
abstract class AuthorizationService extends BaseService implements Interfaces\AuthorizationService
{
    /**
     * @var Configuration
     */
    protected $configService;

    /**
     * @param TokenInterface $token
     *
     * @throws HttpAuthenticationException
     */
    public function connect(TokenInterface $token)
    {
        if (!$this->validateToken($token)) {
            throw new HttpAuthenticationException('Token is not valid', 401);
        }

        $this->getConfigService()->setAuthorizationToken($token->getToken());
        $this->getConfigService()->setTestMode($token->isTest());
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        /** @var PaymentMethodService $paymentMethodService */
        $paymentMethodService = ServiceRegister::getService(PaymentMethodService::CLASS_NAME);
        $websiteProfile = $this->getConfigService()->getWebsiteProfile();

        $this->getConfigService()->removeConfigValue('authToken');
        $this->getConfigService()->removeConfigValue('testMode');
        $this->getConfigService()->removeConfigValue('websiteProfile');
        if ($websiteProfile) {
            $paymentMethodService->clear($websiteProfile->getId());
        }
    }

    /**
     * Returns Authorization token
     *
     * @return string
     */
    public function getAuthToken()
    {
        return $this->getConfigService()->getAuthorizationToken();
    }

    /**
     * @return Configuration
     */
    protected function getConfigService()
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }
}
