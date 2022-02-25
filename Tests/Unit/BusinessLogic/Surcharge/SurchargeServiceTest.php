<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Surcharge;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Surcharge\SurchargeService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Surcharge\SurchargeType;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\BaseTestWithServices;

class SurchargeServiceTest extends BaseTestWithServices
{
    /**
     * @var SurchargeService
     */
    private $surchargeService;

    public function setUp()
    {
        parent::setUp();
        $this->surchargeService = SurchargeService::getInstance();
    }

    public function tearDown()
    {
        SurchargeService::resetInstance();

        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testCalculateSurchargeAmountWhenSubtotalIsZero()
    {
        $type = SurchargeType::FIXED_FEE_AND_PERCENTAGE;
        $fixedAmount = 2;
        $percentage = 10;
        $limit = 20;
        $subtotal = 0;
        $surchargeAmount = $this->surchargeService->calculateSurchargeAmount(
            $type,
            $fixedAmount,
            $percentage,
            $limit,
            $subtotal
        );

        $this->assertEquals(2, $surchargeAmount);
    }

    /**
     * @return void
     */
    public function testCalculateSurchargeAmountWhenTypeIsWrong()
    {
        $type = 'something';
        $fixedAmount = 2;
        $percentage = 10;
        $limit = 20;
        $subtotal = 0;
        $surchargeAmount = $this->surchargeService->calculateSurchargeAmount(
            $type,
            $fixedAmount,
            $percentage,
            $limit,
            $subtotal
        );

        $this->assertEquals(0, $surchargeAmount);
    }

    /**
     * @return void
     */
    public function testCalculateSurchargeAmountWhenSurchargeIsHigherThanLimit()
    {
        $type = SurchargeType::FIXED_FEE_AND_PERCENTAGE;
        $fixedAmount = 2;
        $percentage = 100;
        $limit = 20;
        $subtotal = 20;
        $surchargeAmount = $this->surchargeService->calculateSurchargeAmount(
            $type,
            $fixedAmount,
            $percentage,
            $limit,
            $subtotal
        );

        $this->assertEquals(20, $surchargeAmount);
    }
}
