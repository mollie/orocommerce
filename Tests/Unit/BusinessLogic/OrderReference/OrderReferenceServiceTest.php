<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\OrderReference;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Model\OrderReference;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\OrderReferenceService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\RepositoryRegistry;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\BaseTestWithServices;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\ORM\MemoryRepository;

class OrderReferenceServiceTest extends BaseTestWithServices
{
    protected $orderReferenceService;
    protected $orderReferenceRepository;

    public function setUp()
    {
        parent::setUp();
        RepositoryRegistry::registerRepository(OrderReference::CLASS_NAME, MemoryRepository::getClassName());
        $this->orderReferenceService = OrderReferenceService::getInstance();
        $orderReferenceRepository = RepositoryRegistry::getRepository(OrderReference::CLASS_NAME);

        $orderReference = new OrderReference();
        $orderReference->setShopReference('test_payment_reference');
        $orderReference->setMollieReference('tr_WDqYK6vllg');
        $orderReference->setApiMethod(PaymentMethodConfig::API_METHOD_ORDERS);
        $orderReferenceRepository->save($orderReference);
    }

    public function testGetApiMethod()
    {
        $apiMethod = $this->orderReferenceService->getApiMethod('test_payment_reference');

        $this->assertEquals(PaymentMethodConfig::API_METHOD_ORDERS, $apiMethod);
    }
}
