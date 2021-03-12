<?php


namespace Mollie\Bundle\PaymentBundle\Manager;

/**
 * Class MethodNameGenerator
 *
 * @package Mollie\Bundle\PaymentBundle\Manager
 */
class MethodNameGenerator
{
    /**
     * Transform snake case to camel case string
     *
     * @param string $string
     * @param bool $capitalizeFirstCharacter
     *
     * @return string|string[]
     */
    public static function fromSnakeCase($string, $capitalizeFirstCharacter = true)
    {
        $str = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));

        if (!$capitalizeFirstCharacter) {
            $str[0] = strtolower($str[0]);
        }

        return $str;
    }
}
