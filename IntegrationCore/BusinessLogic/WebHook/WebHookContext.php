<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\WebHook;

use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\Logger;

class WebHookContext
{
    private static $startLevel = 0;

    public static function start()
    {
        static::$startLevel++;
        Logger::logDebug('WebHook context execution started.', 'Core', array('level' => static::$startLevel));
    }

    public static function stop()
    {
        static::$startLevel--;
        Logger::logDebug('WebHook context execution stopped.', 'Core', array('level' => static::$startLevel));
    }

    /**
     * Wraps provided callback into a callback that executes only if web hook context is not started
     *
     * @param \Closure $callback
     *
     * @return \Closure
     */
    public static function getProtectedCallable(\Closure $callback)
    {
        return function () use($callback) {
            if (!WebHookContext::isStarted()) {
                return call_user_func_array($callback, func_get_args());
            }

            return null;
        };
    }

    public static function isStarted()
    {
        return static::$startLevel > 0;
    }
}