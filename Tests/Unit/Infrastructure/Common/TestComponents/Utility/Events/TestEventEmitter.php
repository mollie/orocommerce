<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\Utility\Events;

use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Utility\Events\Event;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Utility\Events\EventEmitter;

class TestEventEmitter extends EventEmitter
{
    public function fire(Event $event)
    {
        parent::fire($event);
    }
}
