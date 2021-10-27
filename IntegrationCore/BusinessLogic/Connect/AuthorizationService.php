<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Connect;

use DateTime;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\Interfaces\AuthorizationService as AuthorizationInterface;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Connect\DTO\AuthInfo;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;

/**
 * Class AuthorizationService
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Connect
 */
abstract class AuthorizationService implements AuthorizationInterface
{
    const AUTHORIZE_URL = 'https://www.mollie.com/oauth2/authorize';

    /**
     * @var Configuration
     */
    protected $configuration;
    /**
     * @var TokenService
     */
    protected $tokenService;

    /**
     * AuthorizationService constructor.
     */
    public function __construct()
    {
        $this->configuration = ServiceRegister::getService(Configuration::CLASS_NAME);
        $this->tokenService = ServiceRegister::getService(TokenService::CLASS_NAME);
    }


    /**
     * Gets string of Mollie permissions that are needed for application
     *
     * @return array
     */
    abstract public function getApplicationPermissions();

    /**
     * Gets clients id
     *
     * @return string
     */
    abstract public function getClientId();

    /**
     * Gets callback url
     *
     * @return string
     */
    abstract public function getRedirectUrl();

    /**
     * Get client secret
     *
     * @return string
     */
    abstract public function getClientSecret();

    /**
     * This function should generate Mollie authorize URL in the given language
     *
     * @param string $locale
     *
     * @return string
     */
    public function getAuthorizeUrl($locale, $state = null)
    {
        $params = array(
            'client_id' => $this->getClientId(),
            'redirect_uri' => $this->getRedirectUrl(),
            'state' => $state ?: $this->generateStateString(),
            'scope' => $this->formatApplicationPermissions(),
            'response_type' => 'code',
            'approval_prompt' => 'force',
            'locale' => $locale,
        );

        return static::AUTHORIZE_URL . '?' . http_build_query($params);
    }

    /**
     * @return AuthInfo|null
     * @throws UnprocessableEntityRequestException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function getAuthInfo()
    {
        $authInfo = $this->configuration->getAuthorizationInfo();
        if (!$authInfo) {
            return null;
        }

        $currentTime = $this->getCurrentTimeInSeconds();
        if ($authInfo->getAccessTokenDuration() < $currentTime) {
            $authInfo = $this->tokenService->refreshToken($authInfo->getRefreshToken());
        }

        return $authInfo;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     */
    abstract public function getAuthToken();

    /**
     * Generates state string
     *
     * @return string
     */
    private function generateStateString()
    {
        $state = md5(uniqid('', true));
        $this->configuration->setStateString($state);

        return $state;
    }

    /**
     * @return string
     */
    private function formatApplicationPermissions()
    {
        $formattedPermission = '';
        foreach ($this->getApplicationPermissions() as $permission) {
            $formattedPermission .= $permission . ' ';
        }

        return trim($formattedPermission);
    }

    /**
     * Returns current time in seconds
     *
     * @return int
     */
    private function getCurrentTimeInSeconds()
    {
        $time = new DateTime('now');

        return $time->getTimestamp();
    }
}
