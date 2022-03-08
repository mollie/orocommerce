<?php

namespace Mollie\Bundle\PaymentBundle\EventListener;

use Mollie\Bundle\PaymentBundle\Exceptions\MollieOperationForbiddenException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

/**
 * Class KernelEventListener
 *
 * @package Mollie\Bundle\PaymentBundle\EventListener
 */
class KernelEventListener
{
    /**
     * @param ExceptionEvent $exceptionEvent
     */
    public function onException(ExceptionEvent $exceptionEvent): void
    {
        $exception = $exceptionEvent->getThrowable();
        if ($exception instanceof MollieOperationForbiddenException) {
            $exceptionEvent->allowCustomResponseCode();
            $exceptionEvent->setResponse(
                new JsonResponse(
                    [
                        'success' => false,
                        'message' => $exception->getMessage(),
                    ],
                    422
                )
            );
        }
    }
}
