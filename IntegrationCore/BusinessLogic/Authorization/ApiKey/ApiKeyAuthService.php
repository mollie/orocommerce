<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\ApiKey;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\AuthorizationService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\Interfaces\TokenInterface;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpAuthenticationException;

/**
 * Class AuthorizationService
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\ApiKey
 */
class ApiKeyAuthService extends AuthorizationService
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;

    /**
     * @param TokenInterface $token
     *
     * @throws HttpAuthenticationException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function connect(TokenInterface $token)
    {
        parent::connect($token);
        $this->configService->setWebsiteProfile($this->getProxy()->getCurrentProfile());
    }

    /**
     * Attempts to connect to Mollie API with provided API key.
     *
     * @param TokenInterface $token
     *
     * @return bool
     */
    public function validateToken(TokenInterface $token)
    {
        $configService = $this->getConfigService();
        $proxy = $this->getProxy();

        return $configService->doWithContext(
            'token_verification',
            function () use ($token, $configService, $proxy) {
                $configService->setAuthorizationToken($token->getToken());
                $configService->setTestMode($token->isTest());

                try {
                    $proxy->getCurrentProfile();
                    $result = true;
                } catch (\Exception $e) {
                    $result = false;
                }

                $configService->removeConfigValue('authToken');
                $configService->removeConfigValue('testMode');

                return $result;
            }
        );
    }
}
