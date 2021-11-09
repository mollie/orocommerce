<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\CheckoutLink;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\ApiKey\ApiKeyAuthService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Authorization\Interfaces\AuthorizationService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\CheckoutLink\CheckoutLinkService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\CheckoutLink\Exceptions\CheckoutLinkNotAvailableException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\OrgToken\ProxyDataProvider;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Proxy;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Model\OrderReference;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\OrderReferenceService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\OrderService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Payments\PaymentService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpRequestException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpClient;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpResponse;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\RepositoryRegistry;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\BaseTestWithServices;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\ORM\MemoryRepository;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\TestHttpClient;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestServiceRegister;

/**
 * Class CheckoutLinkTest
 *
 * @package Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\CheckoutLink
 */
class CheckoutLinkTest extends BaseTestWithServices
{
    /**
     * @var TestHttpClient
     */
    public $httpClient;
    /**
     * @var ProxyDataProvider
     */
    public $proxyTransformer;
    /**
     * @var CheckoutLinkService
     */
    private $checkoutLinkService;
    /**
     * @var RepositoryInterface
     */
    private $orderReferenceRepository;

    public function setUp()
    {
        parent::setUp();

        $me = $this;

        RepositoryRegistry::registerRepository(OrderReference::CLASS_NAME, MemoryRepository::getClassName());

        $this->httpClient = new TestHttpClient();
        $this->proxyTransformer = new ProxyDataProvider();
        TestServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () use ($me) {
                return $me->httpClient;
            }
        );
        TestServiceRegister::registerService(
            Proxy::CLASS_NAME,
            function () use ($me) {
                return new Proxy($me->shopConfig, $me->httpClient, $me->proxyTransformer);
            }
        );

        TestServiceRegister::registerService(
            OrderReferenceService::CLASS_NAME,
            function () {
                return OrderReferenceService::getInstance();
            }
        );

        TestServiceRegister::registerService(
            PaymentService::CLASS_NAME,
            function () {
                return PaymentService::getInstance();
            }
        );

        TestServiceRegister::registerService(
            OrderService::CLASS_NAME,
            function () {
                return OrderService::getInstance();
            }
        );

        TestServiceRegister::registerService(
            AuthorizationService::CLASS_NAME,
            function () use ($me) {
                return ApiKeyAuthService::getInstance();
            }
        );

        $this->shopConfig->setAuthorizationToken('test_token');
        $this->shopConfig->setTestMode(true);
        $this->checkoutLinkService = CheckoutLinkService::getInstance();
        $this->orderReferenceRepository = RepositoryRegistry::getRepository(OrderReference::CLASS_NAME);
        $this->setReferences();
    }

    public function tearDown()
    {
        CheckoutLinkService::resetInstance();
        OrderService::resetInstance();
        PaymentService::resetInstance();

        parent::tearDown();
    }

    /**
     * @throws CheckoutLinkNotAvailableException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     */
    public function testGetCheckoutLinkWithNonExistingReference()
    {
        $this->expectException('Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\CheckoutLink\Exceptions\CheckoutLinkNotAvailableException');
        $this->checkoutLinkService->getCheckoutLink('non_exist_reference');
    }

    /**
     * @throws CheckoutLinkNotAvailableException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     */
    public function testGetCheckoutLinkWithPayment()
    {
        $this->httpClient->setMockResponses(array($this->getMockOrderResponse('paymentCreate')));
        $link = $this->checkoutLinkService->getCheckoutLink('test_payment_reference');

        $this->assertInstanceOf('Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Link', $link);
        $this->assertEquals('https://www.mollie.com/payscreen/select-method/7UhSN1zuXS', $link->getHref());
    }

    /**
     * @throws CheckoutLinkNotAvailableException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     */
    public function testGetCheckoutLinkWithPaymentDoesntHaveCheckoutUrl()
    {
        $this->httpClient->setMockResponses(array(
            $this->getMockOrderResponse('paymentCreate', true),
            $this->getMockOrderResponse('paymentCreate'),
        ));

        $this->checkoutLinkService->getCheckoutLink('test_payment_reference');
        $callHistory = $this->httpClient->getHistory();
        $this->assertCount(2, $callHistory);
        $this->assertEquals(Proxy::HTTP_METHOD_POST, $callHistory[1]['method']);
    }

    /**
     * @throws CheckoutLinkNotAvailableException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     */
    public function testGetCheckoutLinkWithOrders()
    {
        $this->httpClient->setMockResponses(array($this->getMockOrderResponse('orderResponse')));
        $link = $this->checkoutLinkService->getCheckoutLink('test_order_reference');

        $this->assertInstanceOf('Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Link', $link);
        $this->assertEquals('https://www.mollie.com/payscreen/order/checkout/pbjz8x', $link->getHref());
    }

    /**
     * @throws CheckoutLinkNotAvailableException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     */
    public function testGetCheckoutLinkWithOrdersWithExpiredStatus()
    {
        $this->httpClient->setMockResponses(array(
            $this->getMockOrderResponse('orderResponse', true, 'expired'),
            $this->getMockOrderResponse('orderResponse'),
            $this->getMockOrderResponse('orderResponse'),
        ));

        $link = $this->checkoutLinkService->getCheckoutLink('test_order_reference');
        $this->assertInstanceOf('Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Link', $link);
        $this->assertEquals('https://www.mollie.com/payscreen/order/checkout/pbjz8x', $link->getHref());
        $callHistory = $this->httpClient->getHistory();
        $this->assertCount(3, $callHistory);
        $this->assertEquals('POST', $callHistory[1]['method']);
    }

    /**
     * @throws CheckoutLinkNotAvailableException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws UnprocessableEntityRequestException
     */
    public function testGetCheckoutLinkWithOrdersWhenIsInTerminalState()
    {
        $this->httpClient->setMockResponses(array(
            $this->getMockOrderResponse('orderResponse', true, 'paid')));
        $this->expectException('Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\CheckoutLink\Exceptions\CheckoutLinkNotAvailableException');
        $this->checkoutLinkService->getCheckoutLink('test_order_reference');
    }

    /**
     * @param $jsonFile
     *
     * @param bool $removeCheckoutUrl
     * @param null $status
     *
     * @return HttpResponse
     */
    protected function getMockOrderResponse($jsonFile, $removeCheckoutUrl = false, $status = null)
    {
        $response = json_decode(file_get_contents(__DIR__ . "/../Common/ApiResponses/{$jsonFile}.json"), true);
        if ($removeCheckoutUrl) {
            unset($response['_links']['checkout']);
        }

        if ($status) {
            $response['status'] = $status;
        }

        return new HttpResponse(200, array(), json_encode($response));
    }

    protected function setReferences()
    {
        $orderReference = new OrderReference();
        $orderReference->setShopReference('test_payment_reference');
        $orderReference->setMollieReference('tr_WDqYK6vllg');
        $orderReference->setApiMethod(PaymentMethodConfig::API_METHOD_PAYMENT);
        $this->orderReferenceRepository->save($orderReference);

        $orderReference1 = new OrderReference();
        $orderReference1->setShopReference('test_order_reference');
        $orderReference1->setMollieReference('ord_pbjz8x');
        $orderReference1->setApiMethod(PaymentMethodConfig::API_METHOD_ORDERS);
        $this->orderReferenceRepository->save($orderReference1);
    }
}
