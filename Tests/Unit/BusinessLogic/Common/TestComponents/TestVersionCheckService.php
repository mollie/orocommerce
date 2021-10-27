<?php


namespace Mollie\Bundle\PaymentBundle\Tests\Unit\BusinessLogic\Common\TestComponents;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\VersionCheck\VersionCheckService;

class TestVersionCheckService extends VersionCheckService
{
    private $callHistory;

    /**
     * {@inheritdoc}
     */
    protected function flashMessage($latestVersion)
    {
        $this->callHistory[] = array('latestverion' => $latestVersion);

        return $latestVersion;
    }

    public function getCallHistory()
    {
        return $this->callHistory;
    }
}
