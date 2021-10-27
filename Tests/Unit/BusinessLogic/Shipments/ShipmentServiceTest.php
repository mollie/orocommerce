<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Shipments;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\ApiKey\ApiKeyAuthService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\Interfaces\AuthorizationService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Order;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\WebsiteProfile;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\OrgToken\ProxyDataProvider;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Proxy;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Model\OrderReference;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\OrderReferenceService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Shipments\ShipmentService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpClient;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpResponse;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\RepositoryRegistry;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\BaseTestWithServices;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\ORM\MemoryRepository;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\TestHttpClient;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestServiceRegister;

/**
 * Class ShipmentServiceTest
 *
 * @package Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Shipments
 */
class ShipmentServiceTest extends BaseTestWithServices
{
    /**
     * @var TestHttpClient
     */
    public $httpClient;
    /**
     * @var ShipmentService
     */
    protected $shipmentService;


    public function setUp()
    {
        parent::setUp();

        $me = $this;

        RepositoryRegistry::registerRepository(OrderReference::CLASS_NAME, MemoryRepository::getClassName());
        $this->httpClient = new TestHttpClient();
        TestServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () use ($me) {
                return $me->httpClient;
            }
        );
        TestServiceRegister::registerService(
            Proxy::CLASS_NAME,
            function () use ($me) {
                return new Proxy($me->shopConfig, $me->httpClient, new ProxyDataProvider());
            }
        );

        TestServiceRegister::registerService(
            OrderReferenceService::CLASS_NAME,
            function () {
                return OrderReferenceService::getInstance();
            }
        );

        TestServiceRegister::registerService(
            AuthorizationService::CLASS_NAME,
            function () {
                return ApiKeyAuthService::getInstance();
            }
        );

        $this->shipmentService = ShipmentService::getInstance();
        $this->shopConfig->setAuthorizationToken('test_token');
        $this->shopConfig->setTestMode(true);
        $testProfile = new WebsiteProfile();
        $testProfile->setId('pfl_htsmhPNGw3');
        $this->shopConfig->setWebsiteProfile($testProfile);
        $this->orderReferenceRepository = RepositoryRegistry::getRepository(OrderReference::CLASS_NAME);
    }

    public function tearDown()
    {
        ShipmentService::resetInstance();

        parent::tearDown();
    }

    /**
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Exceptions\ReferenceNotFoundException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function testGetShipments()
    {
        $this->httpClient->setMockResponses(array($this->getMockAllShipments()));
        $this->setUpTestOrderReferences('test_reference_id');
        $shopReference = 'test_reference_id';
        $shipments = $this->shipmentService->getShipments($shopReference);
        $this->assertCount(2, $shipments);

        $this->assertInstanceOf('Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Shipment', $shipments[0]);
    }

    /**
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Exceptions\ReferenceNotFoundException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function testCreateShipmentWhenPaymentsApiIsUsed()
    {
        $shopReference = 'test_reference_id';
        $this->setUpTestOrderReferences($shopReference, PaymentMethodConfig::API_METHOD_PAYMENT);
        $this->expectException('\Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Exceptions\ReferenceNotFoundException');
        $this->shipmentService->shipOrder($shopReference);
    }

    /**
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Exceptions\ReferenceNotFoundException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function testGetShipmentsWhenReferenceNotFound()
    {
        $shopReference = 'unknown_reference';

        $this->expectException('\Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Exceptions\ReferenceNotFoundException');
        $this->shipmentService->getShipments($shopReference);
    }

    /**
     * @return HttpResponse
     */
    protected function getMockAllShipments()
    {
        $response = file_get_contents(__DIR__ . '/../Common/ApiResponses/allShipments.json');

        return new HttpResponse(200, array(), $response);
    }

    /**
     * @param string $shopReference
     * @param string $method
     */
    protected function setUpTestOrderReferences($shopReference, $method = PaymentMethodConfig::API_METHOD_ORDERS)
    {
        $payload = file_get_contents(__DIR__ . '/../Common/ApiResponses/orderResponse.json');
        $order = Order::fromArray(json_decode($payload, true));
        OrderReferenceService::getInstance()->updateOrderReference(
            $order,
            $shopReference,
            $method
        );
    }
}
