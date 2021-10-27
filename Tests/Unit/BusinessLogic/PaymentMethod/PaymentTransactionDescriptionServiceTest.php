<?php


namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\PaymentMethod;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\DTO\DescriptionParameters;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\BaseTestWithServices;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents\TestPaymentTransactionDescriptionService;

class PaymentTransactionDescriptionServiceTest extends BaseTestWithServices
{
    protected $transactionDescriptionService;

    public function setUp()
    {
        parent::setUp();

        $this->transactionDescriptionService = TestPaymentTransactionDescriptionService::getInstance();
    }

    public function tearDown()
    {
        TestPaymentTransactionDescriptionService::resetInstance();

        parent::tearDown();
    }

    public function testTransactionDescriptionWithAllParameters()
    {
        $orderNumber = 100;
        $storeName = 'Test Store';
        $firstName = 'John';
        $lastName = 'Doe';
        $company = 'Test Company';
        $cartNumber = 'CART001';

        $this->transactionDescriptionService->mockDescription = 'Store: {storeName}, Order number: {orderNumber}, Customer: {customerFirstname} {customerLastname}, Company: {customerCompany}, cart: {cartNumber}';

        $parameters = new DescriptionParameters($orderNumber, $storeName, $firstName, $lastName, $company, $cartNumber);
        $description = $this->transactionDescriptionService->formatPaymentDescription($parameters, 'test');
        $expected = "Store: $storeName, Order number: $orderNumber, Customer: $firstName $lastName, Company: $company, cart: $cartNumber";

        $this->assertEquals($expected, $description);
    }

    public function testTransactionDescriptionWithSomeParameters()
    {
        $this->transactionDescriptionService->mockDescription = 'Order number: {orderNumber}, Customer: {customerLastname}';
        $orderNumber = 100;
        $lastName = 'Doe';

        $parameters = DescriptionParameters::fromArray(array('orderNumber' => $orderNumber, 'customerLastname' => $lastName));

        $description = $this->transactionDescriptionService->formatPaymentDescription($parameters, 'test');
        $expected = "Order number: $orderNumber, Customer: $lastName";

        $this->assertEquals($expected, $description);
    }
}
