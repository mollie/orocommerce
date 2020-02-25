<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Amount;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Order;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\OrderLine;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Shipment;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Payment;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\PaymentMethod;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Refunds\Refund;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\TokenPermission;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\WebsiteProfile;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpClient;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpResponse;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\LogData;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\Logger;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Configuration;

/**
 * Class Proxy. In charge for communication with Mollie API.
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http
 */
class Proxy
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Mollie base API URL.
     */
    const BASE_URL = 'https://api.mollie.com/';
    /**
     * Mollie API version
     */
    const API_VERSION = 'v2/';
    /**
     * Unauthorized HTTP status code.
     */
    const HTTP_STATUS_CODE_UNAUTHORIZED = 401;
    /**
     * Unprocessable entity status code
     */
    const HTTP_STATUS_CODE_UNPROCESSABLE = 422;
    /**
     * HTTP GET method
     */
    const HTTP_METHOD_GET = 'GET';
    /**
     * HTTP POST method
     */
    const HTTP_METHOD_POST = 'POST';
    /**
     * HTTP PUT method
     */
    const HTTP_METHOD_PATCH = 'PATCH';
    /**
     * HTTP DELETE method
     */
    const HTTP_METHOD_DELETE = 'DELETE';
    /**
     * HTTP Client.
     *
     * @var HttpClient
     */
    private $client;
    /**
     * @var Configuration
     */
    private $configService;
    /**
     * @var ProxyTransformer
     */
    private $transformer;

    /**
     * Proxy constructor.
     *
     * @param Configuration $configService Configuration service.
     * @param HttpClient $client System HTTP client.
     * @param ProxyTransformer $transformer
     */
    public function __construct(Configuration $configService, HttpClient $client, ProxyTransformer $transformer)
    {
        $this->client = $client;
        $this->configService = $configService;
        $this->transformer = $transformer;
    }

    public function createLog(LogData $data)
    {
        // TODO: Implement this when API endpoint is available
    }

    /**
     * Cancel order through Orders API
     *
     * @param string $orderId
     *
     * @return Order
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     */
    public function cancelOrder($orderId)
    {
        $response = $this->call(self::HTTP_METHOD_DELETE, "/orders/{$orderId}");
        $result = $response->decodeBodyAsJson();

        return is_array($result) ? Order::fromArray($result) : new Order();
    }

    /**
     * @param string $orderId Mollie order identifier
     * @param string $orderLineId Mollie order line identifier
     * @param OrderLine $updatedOrderLine dto for update
     *
     * @return Order
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     */
    public function updateOrderLine($orderId, $orderLineId, OrderLine $updatedOrderLine)
    {
        $requestBody = $this->transformer->transformOrderLinesForUpdate($updatedOrderLine);
        $result = $this->call(self::HTTP_METHOD_PATCH, "/orders/{$orderId}/lines/$orderLineId", $requestBody);

        return is_array($result) ? Order::fromArray($result) : new Order();
    }

    /**
     * @param string $orderId
     * @param Order $order
     *
     * @return Order
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     */
    public function updateOrder($orderId, Order $order)
    {
        $requestBody = $this->transformer->transformOrderForUpdate($order);
        $response = $this->call(self::HTTP_METHOD_PATCH, "/orders/$orderId", $requestBody);
        $result = $response->decodeBodyAsJson();

        return is_array($result) ? Order::fromArray($result) : new Order();
    }

    /**
     * Cancel payment through Payments API
     *
     * @param string $paymentId
     *
     * @return Payment
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     */
    public function cancelPayment($paymentId)
    {
        $response = $this->call(self::HTTP_METHOD_DELETE, "/payments/{$paymentId}");
        $result = $response->decodeBodyAsJson();

        return is_array($result) ? Payment::fromArray($result) : new Payment();
    }

    /**
     * @param Refund $refund data for payment refund
     * @param string $paymentId payment identifier
     *
     * @return Refund
     *
     * @throws HttpAuthenticationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     * @throws HttpCommunicationException
     */
    public function createPaymentRefund(Refund $refund, $paymentId)
    {
        $refundData = $this->transformer->transformPaymentRefund($refund);
        $response = $this->call(static::HTTP_METHOD_POST, "/payments/{$paymentId}/refunds", $refundData);
        $result = $response->decodeBodyAsJson();

        return is_array($result) ? Refund::fromArray($result) : new Refund();
    }

    /**
     * @param Refund $refund
     * @param string $orderId
     *
     * @return Refund
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     */
    public function createOrderLinesRefund(Refund $refund, $orderId)
    {
        $refundData = $this->transformer->transformOrderLinesRefund($refund);
        $response = $this->call(self::HTTP_METHOD_POST, "orders/{$orderId}/refunds", $refundData);
        $result = $response->decodeBodyAsJson();

        return is_array($result) ? Refund::fromArray($result) : new Refund();
    }

    /**
     * Gets current token permission list
     *
     * @return TokenPermission[]
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     */
    public function getAccessTokenPermissions()
    {
        $response = $this->call(self::HTTP_METHOD_GET, '/permissions');
        $result = $response->decodeBodyAsJson();

        if (empty($result['_embedded']['permissions'])) {
            return array();
        }

        return TokenPermission::fromArrayBatch($result['_embedded']['permissions']);
    }

    /**
     * Gets list of website profiles from Mollie API
     *
     * @return WebsiteProfile[]
     *
     * @throws HttpAuthenticationException
     * @throws UnprocessableEntityRequestException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function getWebsiteProfiles()
    {
        $response = $this->call(self::HTTP_METHOD_GET, '/profiles?limit=250');
        $result = $response->decodeBodyAsJson();

        return WebsiteProfile::fromArrayBatch(
            !empty($result['_embedded']['profiles']) ? $result['_embedded']['profiles'] : array()
        );
    }

    /**
     * Gets all payment methods that Mollie API offers and can be activated by the Organization
     *
     * @param string $profileId Mollie website profile id
     *
     * @return PaymentMethod[]
     *
     * @throws UnprocessableEntityRequestException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function getAllPaymentMethods($profileId)
    {
        $response = $this->call(self::HTTP_METHOD_GET, "/methods/all?profileId={$profileId}");
        $result = $response->decodeBodyAsJson();

        return PaymentMethod::fromArrayBatch(
            !empty($result['_embedded']['methods']) ? $result['_embedded']['methods'] : array()
        );
    }

    /**
     * Gets all enabled payment methods from Mollie API
     *
     * @param string $profileId Mollie website profile id
     *
     * @param string|null $billingCountry The billing country of your customer in ISO 3166-1 alpha-2 format.
     * @param Amount|null $amount
     *
     * @return PaymentMethod[]
     *
     * @throws UnprocessableEntityRequestException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function getEnabledPaymentMethods($profileId, $billingCountry = null, $amount = null)
    {
        $params = array(
            'includeWallets' => 'applepay',
            'resource' => 'orders',
            'profileId' => $profileId,
        );
        if (!empty($billingCountry)) {
            $params['billingCountry'] = $billingCountry;
        }

        if ($amount) {
            $params['amount'] = $amount->toArray();
        }

        $queryString = http_build_query($params);

        $response = $this->call(self::HTTP_METHOD_GET, "/methods?{$queryString}");
        $result = $response->decodeBodyAsJson();

        return PaymentMethod::fromArrayBatch(
            !empty($result['_embedded']['methods']) ? $result['_embedded']['methods'] : array()
        );
    }

    /**
     * Gets all enabled payment methods from Mollie API in form of a dictionary, where dictionary key is payment method id and
     * value is payment method DTO
     *
     * @param string $profileId Mollie website profile id
     * @param string|null $billingCountry The billing country of your customer in ISO 3166-1 alpha-2 format.
     * @param Amount|null $amount
     *
     * @return PaymentMethod[]
     *
     * @throws UnprocessableEntityRequestException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function getEnabledPaymentMethodsMap($profileId, $billingCountry = null, $amount = null)
    {
        $paymentMethodsMap = array();
        $paymentMethods = $this->getEnabledPaymentMethods($profileId, $billingCountry, $amount);
        foreach ($paymentMethods as $paymentMethod) {
            $paymentMethodsMap[$paymentMethod->getId()] = $paymentMethod;
        }

        return $paymentMethodsMap;
    }

    /**
     * Creates new payment on Mollie
     *
     * @param Payment $payment Data for a new payment
     *
     * @return Payment Created payment
     *
     * @throws UnprocessableEntityRequestException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function createPayment(Payment $payment)
    {
        $paymentData = $this->transformer->transformPayment($payment);
        $response = $this->call(self::HTTP_METHOD_POST, '/payments', $paymentData);
        $result = $response->decodeBodyAsJson();

        return Payment::fromArray($result);
    }

    /**
     * Gets payment by its id
     *
     * @param string $paymentId Mollie payment id
     *
     * @return Payment
     *
     * @throws UnprocessableEntityRequestException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function getPayment($paymentId)
    {
        $response = $this->call(self::HTTP_METHOD_GET, "/payments/{$paymentId}?embed=refunds");
        $result = $response->decodeBodyAsJson();

        return is_array($result) ? Payment::fromArray($result) : new Payment();
    }

    /**
     * Creates new order on Mollie
     *
     * @param Order $order Data for a new order
     *
     * @return Order Created order
     *
     * @throws UnprocessableEntityRequestException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function createOrder(Order $order)
    {
        $orderData = $this->transformer->transformOrder($order);
        $response = $this->call(self::HTTP_METHOD_POST, '/orders', $orderData);
        $result = $response->decodeBodyAsJson();

        return !empty($result['id']) ? $this->getOrder($result['id']) : Order::fromArray($result);
    }

    /**
     * Returns order by its id
     *
     * @param string $orderId Mollie order id
     *
     * @return Order
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     */
    public function getOrder($orderId)
    {
        $response = $this->call(self::HTTP_METHOD_GET, "orders/{$orderId}?embed=payments,refunds");
        $result = $response->decodeBodyAsJson();

        return is_array($result) ? Order::fromArray($result) : new Order();
    }

    /**
     * Creates order shipment on Mollie
     *
     * @param Shipment $shipment
     *
     * @return Shipment Created shipment
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     */
    public function createShipment(Shipment $shipment)
    {
        $shipmentData = $this->transformer->transformShipment($shipment);
        $response = $this->call(
            self::HTTP_METHOD_POST,
            "/orders/{$shipment->getOrderId()}/shipments",
            $shipmentData
        );
        $result = $response->decodeBodyAsJson();

        return is_array($result) ? Shipment::fromArray($result) : new Shipment();
    }

    /**
     * Makes a HTTP call and returns response.
     *
     * @param string $method HTTP method (GET, POST, PUT, etc.).
     * @param string $endpoint Endpoint resource on remote API.
     * @param array $body Request payload body.
     *
     * @return HttpResponse Response from request.
     *
     * @throws HttpAuthenticationException
     * @throws UnprocessableEntityRequestException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    protected function call($method, $endpoint, array $body = array())
    {
        $endpoint = ltrim($endpoint, '/');

        $response = $this->client->request(
            $method,
            $this->getRequestUrl($method, $endpoint),
            $this->getRequestHeaders(),
            $this->getBodyAsString($method, $endpoint, $body)
        );

        $this->validateResponse($response);

        return $response;
    }

    /**
     * Creates full request URL for a given endpoint
     *
     * @param string $method HTTP method (GET, POST, PUT, etc.).
     * @param string $endpoint Endpoint resource on remote API.
     *
     * @return string
     */
    protected function getRequestUrl($method, $endpoint)
    {
        $url = static::BASE_URL . static::API_VERSION . $endpoint;

        if ($this->isTestMode($endpoint) && strtoupper($method) === self::HTTP_METHOD_GET) {
            $url .= false === strpos($url, '?') ? '?' : '&';
            $url .= 'testmode=true';
        }

        return $url;
    }

    /**
     * @param string $method HTTP method (GET, POST, PUT, etc.).
     * @param string $endpoint Endpoint resource on remote API.
     * @param array $body Request payload body.
     *
     * @return false|string
     */
    protected function getBodyAsString($method, $endpoint, array $body = array())
    {
        if (strtoupper($method) === self::HTTP_METHOD_GET) {
            return '';
        }

        if ($this->isTestMode($endpoint)) {
            $body['testmode'] = true;
        }

        return json_encode($body);
    }

    /**
     * Checks if test mode is enabled for a provided endpoint
     *
     * @param string $endpoint Endpoint resource on remote API.
     *
     * @return bool True if test mode is on; false otherwise
     */
    protected function isTestMode($endpoint)
    {
        $unsupportedTestModeEndpoints = array('permissions');

        // Test mode is on if it is configured and requesting endpoint supports test mode
        return $this->configService->isTestMode() && !in_array($endpoint, $unsupportedTestModeEndpoints, true);
    }

    /**
     * Validates HTTP response.
     *
     * @param HttpResponse $response HTTP response returned from API call.
     *
     * @throws HttpAuthenticationException
     * @throws UnprocessableEntityRequestException
     * @throws HttpRequestException
     */
    protected function validateResponse(HttpResponse $response)
    {
        if (!$response->isSuccessful()) {
            $httpCode = $response->getStatus();
            $error = $message = $response->decodeBodyAsJson();
            if (is_array($error)) {
                $message = "{$error['title']}: {$error['detail']}";
            }

            Logger::logInfo(
                'Request to Mollie API was not successful.',
                'Core',
                array(
                    'ApiErrorMessage' => $message
                )
            );
            if ($httpCode === self::HTTP_STATUS_CODE_UNAUTHORIZED) {
                throw new HttpAuthenticationException($message, $httpCode);
            }

            if ($httpCode === self::HTTP_STATUS_CODE_UNPROCESSABLE) {
                throw new UnprocessableEntityRequestException(array_key_exists('field', $error) ? $error['field'] : '', $message, $httpCode);
            }

            throw new HttpRequestException($message, $httpCode);
        }
    }

    /**
     * Returns headers together with authorization entry.
     *
     * @return array Formatted request headers.
     */
    protected function getRequestHeaders()
    {
        $userAgents = array(
            'PHP/'.PHP_VERSION,
            str_replace(
                array(' ', "\t", "\n", "\r"),
                '-',
                $this->configService->getIntegrationName().'/'.$this->configService->getIntegrationVersion()
            ),
            str_replace(
                array(' ', "\t", "\n", "\r"),
                '-',
                $this->configService->getExtensionName().'/'.$this->configService->getExtensionVersion()
            ),
        );

        return array(
            'accept' => 'Accept: application/json',
            'content' => 'Content-Type: application/json',
            'useragent' => 'User-Agent: '.implode(' ', $userAgents),
            'token' => 'Authorization: Bearer ' . $this->configService->getAuthorizationToken(),
        );
    }
}
