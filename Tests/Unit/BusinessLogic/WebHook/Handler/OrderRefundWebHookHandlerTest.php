<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\WebHook\Handler;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Payment;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Refunds\Refund;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Integration\Interfaces\OrderTransitionService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Model\OrderReference;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\OrderReferenceService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Refunds\WebHookHandler\OrderRefundWebHookHandler;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\WebHook\PaymentChangedWebHookEvent;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\RepositoryRegistry;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\BaseTestWithServices;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\ORM\MemoryRepository;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\TestOrderTransitionService;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestServiceRegister;

class OrderRefundWebHookHandlerTest extends BaseTestWithServices
{
    /**
     * @var TestOrderTransitionService
     */
    public $orderTransitionService;
    /**
     * @var OrderRefundWebHookHandlerTest
     */
    private $handler;
    /**
     * @var string
     */
    private $shopReference = 'test_reference_id';

    public function setUp()
    {
        parent::setUp();

        $me = $this;

        $this->handler = new OrderRefundWebHookHandler();
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

    public function testRefundStatusChangeDetection()
    {
        $newPayment = $this->getOrderReferenceData();
        $this->setRefundStatus($newPayment, 'refunded');
        $event = new PaymentChangedWebHookEvent($this->getOrderReference(), $newPayment);

        $this->handler->handle($event);

        $this->assertCount(1, $this->orderTransitionService->getCallHistory());
        $callHistory = $this->orderTransitionService->getCallHistory();
        $this->assertCount(1, $callHistory);
        $this->assertArrayHasKey('refundOrder', $callHistory);
        $this->assertCount(1, $callHistory['refundOrder']);
    }

    public function testRefundStatusChangeIgnored()
    {
        $newPayment = $this->getOrderReferenceData();
        $this->setRefundStatus($newPayment, 'processing');
        $event = new PaymentChangedWebHookEvent($this->getOrderReference(), $newPayment);
        $this->handler->handle($event);
        $this->assertCount(0, $this->orderTransitionService->getCallHistory());
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

    protected function setRefundStatus(Payment $payment, $status)
    {
        $embedded = $payment->getEmbedded();
        /** @var Refund $refund */
        $refund = reset($embedded['refunds']);
        $refund->setStatus($status);
    }
}
