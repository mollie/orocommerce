<?php


namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\MaintenanceMode\MaintenanceModeService;

/**
 * Class TestMaintenanceModeService
 *
 * @package Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common
 */
class TestMaintenanceModeService extends MaintenanceModeService
{
    private $callHistory = array();

    public static $IN_MAINTENANCE = true;

    /**
     * {@inheritdoc}
     */
    protected function isMaintenanceMode()
    {
        return static::$IN_MAINTENANCE;
    }

    /**
     * {@inheritdoc}
     */
    protected function showMaintenanceModeMessage()
    {
        $this->callHistory[] = array('called' => true);
    }

    /**
     * @return array
     */
    public function getCallHistory()
    {
        return $this->callHistory;
    }
}
