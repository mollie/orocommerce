<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\CustomerReference;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\CustomerReference\CustomerReferenceService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\CustomerReference\Model\CustomerReference;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\RepositoryRegistry;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\BaseTestWithServices;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\ORM\MemoryRepository;

class CustomerReferenceServiceTest extends BaseTestWithServices
{
    protected $customerReferenceService;
    protected $customerReferenceRepository;

    public function setUp()
    {
        parent::setUp();
        RepositoryRegistry::registerRepository(CustomerReference::CLASS_NAME, MemoryRepository::getClassName());
        $this->customerReferenceService = CustomerReferenceService::getInstance();
        $this->customerReferenceRepository = RepositoryRegistry::getRepository(CustomerReference::CLASS_NAME);
        $customerReference = new CustomerReference();
        $customerReference->setShopReference('test_customer_reference');
        $customerReference->setMollieReference('cst_Qgfx38x4a1');
        $this->customerReferenceRepository->save($customerReference);
    }

    public function testGetByShopReference()
    {
        $customer = $this->customerReferenceService->getByShopReference('test_customer_reference');

        $this->assertEquals($customer->getShopReference(), 'test_customer_reference');
    }

    public function testGetByMollieReference()
    {
        $customer = $this->customerReferenceService->getByMollieReference('cst_Qgfx38x4a1');

        $this->assertEquals($customer->getMollieReference(), 'cst_Qgfx38x4a1');
    }

    public function testDeleteByShopReference()
    {
        $this->customerReferenceService->deleteByShopReference('test_customer_reference');

        $customerReference = $this->customerReferenceService->getByShopReference('test_customer_reference');

        $this->assertNull($customerReference);
    }
}
