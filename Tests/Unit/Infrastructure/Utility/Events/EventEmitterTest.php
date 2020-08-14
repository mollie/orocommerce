<?php

namespace Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Utility\Events;

use Exception;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Utility\Events\Event;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\Utility\Events\TestBarEvent;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\Utility\Events\TestEventEmitter;
use Mollie\Bundle\PaymentBundle\Tests\Unit\Infrastructure\Common\TestComponents\Utility\Events\TestFooEvent;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class EventEmitterTest extends TestCase
{
    public function testItShouldBePossibleToFireEventWithoutAnySubscribedHandlers()
    {
        $emitter = new TestEventEmitter();

        try {
            $emitter->fire(new TestFooEvent());
        } catch (Exception $ex) {
            $this->fail('It should be possible to fire event without any subscribers.');
        }
    }

    public function testItShouldBePossibleToSubscribeMultipleHandlersToSameEvent()
    {
        $emitter = new TestEventEmitter();
        $handler1Event = null;
        $handler2Event = null;
        $emitter->when(
            TestFooEvent::CLASS_NAME,
            function (TestFooEvent $event) use (&$handler1Event) {
                $handler1Event = $event;
            }
        );
        $emitter->when(
            TestFooEvent::CLASS_NAME,
            function (
                TestFooEvent $event
            ) use (&$handler2Event) {
                $handler2Event = $event;
            }
        );

        $emitter->fire(new TestFooEvent());

        $this->assertNotNull($handler1Event, 'Event emitter must call each subscribed handler.');
        $this->assertNotNull($handler2Event, 'Event emitter must call each subscribed handler.');
    }

    public function testItShouldBePossibleToNotifyOnlySubscribersOnSpecificEvent()
    {
        $emitter = new TestEventEmitter();
        $handler1Event = null;
        $handler2Event = null;
        $emitter->when(
            TestFooEvent::CLASS_NAME,
            function (TestFooEvent $event) use (&$handler1Event) {
                $handler1Event = $event;
            }
        );
        $emitter->when(
            TestBarEvent::CLASS_NAME,
            function (Event $event) use (&$handler2Event) {
                $handler2Event = $event;
            }
        );

        $emitter->fire(new TestFooEvent());

        $this->assertNotNull($handler1Event, 'Event emitter must call each subscribed handler.');
        $this->assertNull($handler2Event, 'Event emitter must call only handlers subscribed to fired event.');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Handler exception
     */
    public function testItShouldBePossibleToTriggerExceptionFromInsideHandlerMethod()
    {
        $emitter = new TestEventEmitter();
        $emitter->when(
            TestFooEvent::CLASS_NAME,
            function () {
                throw new RuntimeException('Handler exception');
            }
        );

        $emitter->fire(new TestFooEvent());

        $this->fail('It should be possible to throw exception from event handler code.');
    }
}
