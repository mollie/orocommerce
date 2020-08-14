<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\WebHook\Handler;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Payment;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Interfaces\OrderTransitionService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Model\OrderReference;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\OrderReferenceService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Payments\WebHookHandler\StatusWebHookHandler;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\WebHook\PaymentChangedWebHookEvent;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\RepositoryRegistry;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\BaseTestWithServices;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\ORM\MemoryRepository;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\TestOrderTransitionService;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestServiceRegister;

class PaymentStatusWebHookHandlerTest extends BaseTestWithServices
{
    /**
     * @var TestOrderTransitionService
     */
    public $orderTransitionService;
    /**
     * @var StatusWebHookHandler
     */
    private $handler;
    /**
     * @var string
     */
    private $shopReference = '12345';

    public function setUp()
    {
        parent::setUp();

        $me = $this;

        $this->handler = new StatusWebHookHandler();
        $this->orderTransitionService = new TestOrderTransitionService();

        RepositoryRegistry::registerRepository(OrderReference::CLASS_NAME, MemoryRepository::getClassName());

        TestServiceRegister::registerService(
            OrderTransitionService::CLASS_NAME,
            function () use ($me) {
                return $me->orderTransitionService;
            }
        );

        $this->setUpTestOrderReferences();
    }

    /**
     * @param string $status
     * @param string $expectedMethodCall
     *
     * @dataProvider statusProvider
     */
    public function testPaymentStatusChangeDetection($status, $expectedMethodCall)
    {
        $newPayment = $this->getOrderReferenceData();
        $newPayment->setStatus($status);
        $event = new PaymentChangedWebHookEvent($this->getOrderReference(), $newPayment);

        $this->handler->handle($event);

        $callHistory = $this->orderTransitionService->getCallHistory($expectedMethodCall);
        $this->assertCount(
            1,
            $callHistory,
            "Payment status change to '{$status}' should trigger OrderTransitionService::{$expectedMethodCall}() method."
        );
        $this->assertEquals($this->shopReference, $callHistory[0]['orderId']);
        $this->assertEquals($newPayment->getMetadata(), $callHistory[0]['metadata']);
    }

    public function testUnknownStatus()
    {
        $newPayment = $this->getOrderReferenceData();
        $newPayment->setStatus('test_unknown_status');
        $event = new PaymentChangedWebHookEvent($this->getOrderReference(), $newPayment);

        $this->handler->handle($event);

        $callHistory = $this->orderTransitionService->getCallHistory();
        $this->assertCount(0, $callHistory);
    }

    public function testUnchangedStatus()
    {
        $newPayment = $this->getOrderReferenceData();
        $newPayment->setStatus('paid');
        $event = new PaymentChangedWebHookEvent($this->getOrderReference(), $newPayment);
        $event->getCurrentPayment()->setStatus($newPayment->getStatus());

        $this->handler->handle($event);

        $callHistory = $this->orderTransitionService->getCallHistory();
        $this->assertCount(0, $callHistory);
    }

    public function statusProvider()
    {
        return array(
            array(
                'status' => 'paid',
                'expectedMethodCall' => 'payOrder',
            ),
            array(
                'status' => 'expired',
                'expectedMethodCall' => 'expireOrder',
            ),
            array(
                'status' => 'canceled',
                'expectedMethodCall' => 'cancelOrder',
            ),
            array(
                'status' => 'authorized',
                'expectedMethodCall' => 'authorizeOrder',
            ),
            array(
                'status' => 'failed',
                'expectedMethodCall' => 'failOrder',
            ),
        );
    }

    protected function setUpTestOrderReferences()
    {
        OrderReferenceService::getInstance()->updateOrderReference(
            $this->getOrderReferenceData(),
            $this->shopReference,
            PaymentMethodConfig::API_METHOD_PAYMENT
        );
    }

    protected function getOrderReference()
    {
        return OrderReferenceService::getInstance()->getByShopReference($this->shopReference);
    }

    /**
     * @return Payment
     */
    protected function getOrderReferenceData()
    {
        return Payment::fromArray(json_decode($this->getMockPaymentJson(), true));
    }

    /**
     * @return string
     */
    protected function getMockPaymentJson()
    {
        return file_get_contents(__DIR__ . '/../../Common/ApiResponses/paymentCreate.json');
    }
}
