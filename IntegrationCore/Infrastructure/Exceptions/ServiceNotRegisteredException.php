<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Exceptions;

/**
 * Class ServiceNotRegisteredException.
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Exceptions
 */
class ServiceNotRegisteredException extends BaseException
{
    /**
     * ServiceNotRegisteredException constructor.
     *
     * @param string $type Type of service. Should be fully qualified class name.
     */
    public function __construct($type)
    {
        parent::__construct("Service of type \"$type\" is not registered.");
    }
}
