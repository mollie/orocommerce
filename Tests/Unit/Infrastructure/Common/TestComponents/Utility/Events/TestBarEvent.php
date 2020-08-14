<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\Utility\Events;

use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Utility\Events\Event;

class TestBarEvent extends Event
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
}
