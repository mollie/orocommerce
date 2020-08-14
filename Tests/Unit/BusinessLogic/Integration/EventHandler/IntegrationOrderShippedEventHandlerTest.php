<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Integration\EventHandler;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Shipment;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Tracking;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Event\IntegrationOrderShippedEvent;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\IntegrationEventHandler\IntegrationOrderShippedEventHandler;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpResponse;

class IntegrationOrderShippedEventHandlerTest extends IntegrationOrderEventHandlerTest
{
    /**
     * @var IntegrationOrderShippedEventHandler
     */
    protected $handler;

    public function setUp()
    {
        parent::setUp();
        $this->handler = new IntegrationOrderShippedEventHandler();
    }

    /**
     * @throws \Exception
     */
    public function testOrderShippingUponIntegrationEvent()
    {
        $event = new IntegrationOrderShippedEvent($this->orderShopReference);
        $this->httpClient->setMockResponses(array($this->getMockApiShipmentResponse()));

        $this->handler->handle($event);

        $apiRequestHistory = $this->httpClient->getHistory();
        $this->assertCount(1, $apiRequestHistory);

        $this->assertContains(
            "orders/{$this->getOrderReferenceOrderData()->getId()}/shipments",
            $apiRequestHistory[0]['url']
        );
        $requestBody = json_decode($apiRequestHistory[0]['body'], true);
        $this->assertEquals(array(), $requestBody['lines']);
        $this->assertArrayNotHasKey('tracking', $requestBody);
    }

    /**
     * @throws \Exception
     */
    public function testOrderShippingWithTracking()
    {
        $tracking = Tracking::fromArray(array('carrier' => 'PostNL', 'code' => '3SKABA000000000'));
        $event = new IntegrationOrderShippedEvent($this->orderShopReference, $tracking);
        $this->httpClient->setMockResponses(array($this->getMockApiShipmentResponse()));

        $this->handler->handle($event);

        $apiRequestHistory = $this->httpClient->getHistory();
        $this->assertCount(1, $apiRequestHistory);

        $requestBody = json_decode($apiRequestHistory[0]['body'], true);
        $this->assertEquals(array(), $requestBody['lines']);
        $this->assertEquals($tracking->toArray(), $requestBody['tracking']);
    }

    /**
     * @throws \Exception
     */
    public function testNotExistingShopReferenceIsIgnored()
    {
        $event = new IntegrationOrderShippedEvent('not_existing_shop_order_id');

        $this->handler->handle($event);

        $this->assertEquals(
            $this->getOrderReferenceOrderData()->toArray(),
            $this->getStoredOrderReferenceData($this->orderShopReference)->getPayload()
        );
    }

    /**
     * @throws \Exception
     */
    public function testPaymentApiShopReferenceIsIgnored()
    {
        $event = new IntegrationOrderShippedEvent($this->paymentShopReference);

        $this->handler->handle($event);

        $this->assertEquals(
            $this->getOrderReferenceOrderData()->toArray(),
            $this->getStoredOrderReferenceData($this->orderShopReference)->getPayload()
        );
    }

    protected function getMockApiShipmentResponse()
    {
        $apiShipmentData = $this->getApiShipmentData();
        return new HttpResponse(200, array(), json_encode($apiShipmentData->toArray()));
    }

    protected function getApiShipmentData()
    {
        return Shipment::fromArray(json_decode($this->getMockShipmentJson(), true));
    }

    protected function getMockShipmentJson()
    {
        return file_get_contents(__DIR__ . '/../../Common/ApiResponses/shipmentResponse.json');
    }
}
