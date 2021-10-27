<?php


namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\MaintenanceMode;

use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\BaseTestWithServices;
use Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestMaintenanceModeService;

class MaintenanceModeServiceTest extends BaseTestWithServices
{
    protected $maintenanceModeService;

    public function setUp()
    {
        parent::setUp();

        $this->maintenanceModeService = TestMaintenanceModeService::getInstance();
    }

    public function tearDown()
    {
        TestMaintenanceModeService::resetInstance();

        parent::tearDown();
    }

    public function testWhenInMaintenanceMode()
    {
        $this->maintenanceModeService->checkMaintenanceMode();

        $this->assertNotEmpty($this->maintenanceModeService->getCallHistory());
    }


    public function testWhenNotInMaintenanceMode()
    {
        TestMaintenanceModeService::$IN_MAINTENANCE = false;
        $this->maintenanceModeService->checkMaintenanceMode();

        $this->assertEmpty($this->maintenanceModeService->getCallHistory());
    }
}
