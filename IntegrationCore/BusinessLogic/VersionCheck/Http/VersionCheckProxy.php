<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\VersionCheck\Http;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Proxy;

/**
 * Class VersionCheckProxy
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\VersionCheck\Http
 */
class VersionCheckProxy extends Proxy
{
    const CLASS_NAME = __CLASS__;

    /**
     * Returns latest published plugin version
     *
     * @param string $versionCheckUrl
     *
     * @return string|null
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getLatestPluginVersion($versionCheckUrl)
    {
        $response = $this->call(self::HTTP_METHOD_GET, $versionCheckUrl);
        $result = $response->decodeBodyAsJson();

        return array_key_exists('version', $result) ? $result['version'] : null;
    }

    /**
     * {@inheritdoc}
     * @param string $method
     * @param string $endpoint
     *
     * @return string
     */
    protected function getRequestUrl($method, $endpoint)
    {
        return $endpoint;
    }

    /**
     * {@inheritdoc}
     * @return array|string[]
     */
    protected function getRequestHeaders()
    {
        $headers = parent::getRequestHeaders();
        unset($headers['token']);

        return $headers;
    }
}
