<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Integration\EventHandler;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Order;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Payment;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\OrgToken\ProxyDataProvider;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Proxy;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\DefaultNotificationChannel;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\Interfaces\DefaultNotificationChannelAdapter;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Notifications\Model\Notification;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Model\OrderReference;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\OrderReferenceService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\OrderService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Shipments\ShipmentService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpClient;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\RepositoryRegistry;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\BaseTestWithServices;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\ORM\MemoryRepository;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\TestHttpClient;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestServiceRegister;

abstract class IntegrationOrderEventHandlerTest extends BaseTestWithServices
{
    /**
     * @var TestHttpClient
     */
    public $httpClient;

    protected $handler;

    /**
     * @var string
     */
    protected $orderShopReference = 'test_reference_id';
    /**
     * @var string
     */
    protected $paymentShopReference = '12345';
    /**
     * @var DefaultNotificationChannelAdapter
     */
    protected $defaultChannel;

    public function setUp()
    {
        parent::setUp();

        RepositoryRegistry::registerRepository(OrderReference::CLASS_NAME, MemoryRepository::getClassName());
        RepositoryRegistry::registerRepository(Notification::getClassName(), MemoryRepository::getClassName());
        $me = $this;

        $this->httpClient = new TestHttpClient();
        $this->defaultChannel = new DefaultNotificationChannel();
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
            OrderService::CLASS_NAME,
            function () {
                return OrderService::getInstance();
            }
        );

        TestServiceRegister::registerService(
            ShipmentService::CLASS_NAME,
            function () {
                return ShipmentService::getInstance();
            }
        );

        TestServiceRegister::registerService(DefaultNotificationChannelAdapter::CLASS_NAME, function () use ($me) {
            return $me->defaultChannel;
        });


        $this->setUpTestOrderReferences();
    }

    public function tearDown()
    {
        OrderReferenceService::resetInstance();
        OrderService::resetInstance();
        ShipmentService::resetInstance();

        parent::tearDown();
    }

    protected function setUpTestOrderReferences()
    {
        OrderReferenceService::getInstance()->updateOrderReference(
            $this->getOrderReferencePaymentData(),
            $this->paymentShopReference,
            PaymentMethodConfig::API_METHOD_PAYMENT
        );
        OrderReferenceService::getInstance()->updateOrderReference(
            $this->getOrderReferenceOrderData(),
            $this->orderShopReference,
            PaymentMethodConfig::API_METHOD_ORDERS
        );
    }

    /**
     * @param string $shopReference Payment or order id
     *
     * @return OrderReference|null
     */
    protected function getStoredOrderReferenceData($shopReference)
    {
        return OrderReferenceService::getInstance()->getByShopReference($shopReference);
    }

    /**
     * @return Payment
     */
    protected function getOrderReferencePaymentData()
    {
        return Payment::fromArray(json_decode($this->getMockPaymentJson(), true));
    }

    /**
     * @return string
     */
    protected function getMockOrderJson()
    {
        return file_get_contents(__DIR__ . '/../../Common/ApiResponses/orderResponse.json');
    }

    /**
     * @return string
     */
    protected function getMockPaymentJson()
    {
        return file_get_contents(__DIR__ . '/../../Common/ApiResponses/paymentCreate.json');
    }


    /**
     * @return Order
     */
    protected function getOrderReferenceOrderData()
    {
        return Order::fromArray(json_decode($this->getMockOrderJson(), true));
    }
}
