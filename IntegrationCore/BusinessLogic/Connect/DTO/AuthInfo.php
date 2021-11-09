<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Connect\DTO;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\BaseDto;

/**
 * Class AuthInfo
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Connect\DTO
 */
class AuthInfo extends BaseDto
{
    /**
     * @var string
     */
    private $accessToken;
    /**
     * @var string
     */
    private $refreshToken;
    /**
     * @var int
     */
    private $accessTokenDuration;

    /**
     * AuthInfo constructor.
     * @param string $accessToken
     * @param string $refreshToken
     * @param int $accessTokenDuration
     */
    public function __construct($accessToken, $refreshToken, $accessTokenDuration)
    {
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->accessTokenDuration = $accessTokenDuration;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @param string $accessToken
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * @param string $refreshToken
     */
    public function setRefreshToken($refreshToken)
    {
        $this->refreshToken = $refreshToken;
    }

    /**
     * @return int
     */
    public function getAccessTokenDuration()
    {
        return $this->accessTokenDuration;
    }

    /**
     * @param int $accessTokenDuration
     */
    public function setAccessTokenDuration($accessTokenDuration)
    {
        $this->accessTokenDuration = $accessTokenDuration;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'access_token' => $this->getAccessToken(),
            'refresh_token' => $this->getRefreshToken(),
            'expires_in' => $this->getAccessTokenDuration(),
        );
    }

    /**
     *
     * @param array $raw
     *
     * @return AuthInfo
     */
    public static function fromArray(array $raw)
    {
        $authInfo = new AuthInfo('', '', 0);
        $authInfo->setAccessToken(static::getValue($raw, 'access_token', null));
        $authInfo->setRefreshToken(static::getValue($raw, 'refresh_token', null));
        $authInfo->setAccessTokenDuration(static::getValue($raw, 'expires_in', null));

        return $authInfo;
    }
}
