<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Integration\EventHandler;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Proxy;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Event\IntegrationOrderCanceledEvent;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Orders\IntegrationEventHandler\IntegrationOrderCanceledEventHandler;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\HttpResponse;

class IntegrationOrderCanceledEventHandlerTest extends IntegrationOrderEventHandlerTest
{

    /**
     * @var IntegrationOrderCanceledEventHandler
     */
    protected $handler;

    public function setUp()
    {
        parent::setUp();
        $this->handler = new IntegrationOrderCanceledEventHandler();
    }

    /**
     * @throws \Exception
     */
    public function testOrderCancellation()
    {
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $this->getMockOrderJson())));
        $event = new IntegrationOrderCanceledEvent($this->orderShopReference);
        $this->handler->handle($event);

        $callHistory = $this->httpClient->getHistory();
        $this->assertCount(1, $callHistory);
        $this->assertEquals(Proxy::HTTP_METHOD_DELETE, $callHistory[0]['method']);
        $this->assertContains('orders', $callHistory[0]['url']);
    }

    /**
     * @throws \Exception
     */
    public function testPaymentCancellation()
    {
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $this->getMockPaymentJson())));
        $event = new IntegrationOrderCanceledEvent($this->paymentShopReference);
        $this->handler->handle($event);

        $callHistory = $this->httpClient->getHistory();
        $this->assertCount(1, $callHistory);
        $this->assertEquals(Proxy::HTTP_METHOD_DELETE, $callHistory[0]['method']);
        $this->assertContains('payments', $callHistory[0]['url']);
    }

    /**
     * @throws \Exception
     */
    public function testOrderCancellationWithException()
    {
        $this->httpClient->setMockResponses(array(new HttpResponse(400, array(), $this->getErrorJson())));
        $event = new IntegrationOrderCanceledEvent($this->paymentShopReference);
        $this->expectException('Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Http\Exceptions\HttpRequestException');
        $this->handler->handle($event);
    }

    /**
     * @throws \Exception
     */
    public function testOrderCancellationWithNonExistingReference()
    {
        $event = new IntegrationOrderCanceledEvent('not_exist');
        $this->handler->handle($event);
        $this->assertNull($this->httpClient->getHistory());
    }

    protected function getErrorJson()
    {
        return file_get_contents(__DIR__ . '/../../Common/ApiResponses/errorOrderCancel.json');
    }
}
