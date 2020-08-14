<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Integration\EventHandler;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Proxy;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Event\IntegrationOrderBillingAddressChangedEvent;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Event\IntegrationOrderShippingAddressChangedEvent;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\IntegrationEventHandler\IntegrationOrderBillingAddressChangedEventHandler;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\IntegrationEventHandler\IntegrationOrderShippingAddressChangedEventHandler;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpResponse;

/**
 * Class IntegrationOrderBillingAddressChangedEvenHandler
 *
 * @package Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Integration\EventHandler
 */
class IntegrationOrderAddressChangedEvenHandlerTest extends IntegrationOrderEventHandlerTest
{
    /**
     * @throws \Exception
     */
    public function testShippingAddressChanged()
    {
        $handler = new IntegrationOrderShippingAddressChangedEventHandler();
        $order = $this->getOrderReferenceOrderData();
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $this->getMockOrderJson())));
        $event = new IntegrationOrderShippingAddressChangedEvent($this->orderShopReference, $order->getShippingAddress());

        $handler->handle($event);
        $callHistory = $this->httpClient->getHistory();
        $this->assertCount(1, $callHistory);
        $this->assertEquals(Proxy::HTTP_METHOD_PATCH, $callHistory[0]['method']);
        $requestBody = json_decode($callHistory[0]['body'], true);
        $this->assertArrayHasKey('shippingAddress', $requestBody);
        $this->assertArrayNotHasKey('billingAddress', $requestBody);
    }

    /**
     * @throws \Exception
     */
    public function testBillingAddressChanged()
    {
        $handler = new IntegrationOrderBillingAddressChangedEventHandler();
        $order = $this->getOrderReferenceOrderData();
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $this->getMockOrderJson())));
        $event = new IntegrationOrderBillingAddressChangedEvent($this->orderShopReference, $order->getBillingAddress());

        $handler->handle($event);
        $callHistory = $this->httpClient->getHistory();
        $this->assertCount(1, $callHistory);
        $this->assertEquals(Proxy::HTTP_METHOD_PATCH, $callHistory[0]['method']);
        $requestBody = json_decode($callHistory[0]['body'], true);
        $this->assertArrayNotHasKey('shippingAddress', $requestBody);
        $this->assertArrayHasKey('billingAddress', $requestBody);
    }

    /**
     * @throws \Exception
     */
    public function testAddressChangeError()
    {
        $handler = new IntegrationOrderBillingAddressChangedEventHandler();
        $order = $this->getOrderReferenceOrderData();
        $this->httpClient->setMockResponses(array(new HttpResponse(422, array(), $this->getErrorJson())));
        $event = new IntegrationOrderBillingAddressChangedEvent($this->orderShopReference, $order->getBillingAddress());
        $this->expectException('Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException');
        $handler->handle($event);
    }

    protected function getErrorJson()
    {
        return file_get_contents(__DIR__ . '/../../Common/ApiResponses/errorOrderCancel.json');
    }
}
