<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\Utility;

use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Utility\GuidProvider;

class TestGuidProvider extends GuidProvider
{
    private $guid = '';

    public function generateGuid()
    {
        if (empty($this->guid)) {
            return parent::generateGuid();
        }

        return $this->guid;
    }

    /**
     * @param string $guid
     */
    public function setGuid($guid)
    {
        $this->guid = $guid;
    }
}
