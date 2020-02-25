<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO;

class Amount extends BaseDto
{
    /**
     * @var string
     */
    protected $value;
    /**
     * @var string
     */
    protected $currency;

    /**
     * @inheritDoc
     */
    public static function fromArray(array $raw)
    {
        $result = new static();

        $result->setAmountValue(static::getValue($raw, 'value', '0.00'));
        $result->currency = static::getValue($raw, 'currency', 'EUR');

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'value' => $this->value,
            'currency' => $this->currency,
        );
    }

    /**
     * @return string
     */
    public function getAmountValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setAmountValue($value)
    {
        $this->value = number_format((float)$value, 2, '.', '');
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }
}