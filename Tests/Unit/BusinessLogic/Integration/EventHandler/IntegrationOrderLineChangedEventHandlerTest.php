<?php


namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Integration\EventHandler;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\OrderLine;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Proxy;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Event\IntegrationOrderLineChangedEvent;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\IntegrationEventHandler\IntegrationOrderLineChangedEventHandler;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpResponse;

class IntegrationOrderLineChangedEventHandlerTest extends IntegrationOrderEventHandlerTest
{
    /**
     * @var IntegrationOrderLineChangedEventHandler
     */
    protected $handler;


    public function setUp()
    {
        parent::setUp();
        $this->handler = new IntegrationOrderLineChangedEventHandler();
    }

    /**
     * @throws \Exception
     */
    public function testOrderLineChange()
    {
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $this->getMockOrderJson())));

        $event = new IntegrationOrderLineChangedEvent($this->orderShopReference, $this->getLineForUpdate());
        $this->handler->handle($event);

        $callHistory = $this->httpClient->getHistory();
        $this->assertCount(1, $callHistory);
        $this->assertEquals(Proxy::HTTP_METHOD_PATCH, $callHistory[0]['method']);
        $this->assertContains("orders/{$this->getOrderReferenceOrderData()->getId()}/lines", $callHistory[0]['url']);
    }

    /**
     * @throws \Exception
     */
    public function testOrderCancellationWithException()
    {
        $this->httpClient->setMockResponses(array(new HttpResponse(422, array(), $this->getErrorJson())));
        $event = new IntegrationOrderLineChangedEvent($this->orderShopReference, $this->getLineForUpdate());
        $this->expectException('Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Exceptions\UnprocessableEntityRequestException');
        $this->handler->handle($event);
    }

    /**
     * @throws \Exception
     */
    public function testOrderCancellationWithNonExistingReference()
    {
        $event = new IntegrationOrderLineChangedEvent('not_exist', $this->getLineForUpdate());
        $this->handler->handle($event);
        $this->assertNull($this->httpClient->getHistory());
    }

    /**
     * @return OrderLine
     */
    protected function getLineForUpdate()
    {
        $order = $this->getOrderReferenceOrderData();
        $lines = $order->getLines();

        return reset($lines);
    }


    protected function getErrorJson()
    {
        return file_get_contents(__DIR__ . '/../../Common/ApiResponses/errorOrderCancel.json');
    }
}
