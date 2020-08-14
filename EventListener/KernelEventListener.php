<?php

namespace Mollie\Bundle\PaymentBundle\EventListener;

use Mollie\Bundle\PaymentBundle\Exceptions\MollieOperationForbiddenException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * Class KernelEventListener
 *
 * @package Mollie\Bundle\PaymentBundle\EventListener
 */
class KernelEventListener
{
    /**
     * @param GetResponseForExceptionEvent $exceptionEvent
     */
    public function onException(GetResponseForExceptionEvent $exceptionEvent)
    {
        $exception = $exceptionEvent->getException();
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
