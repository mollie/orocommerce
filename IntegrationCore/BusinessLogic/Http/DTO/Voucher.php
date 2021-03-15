<?php


namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO;

/**
 * Class Voucher
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO
 */
class Voucher extends BaseDto
{

    /**
     * @var string
     */
    private $issuer;
    /**
     * @var Amount
     */
    private $amount;

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return array(
            'issuer' => $this->issuer,
            'amount' => $this->amount->toArray(),
        );
    }

    /**
     * @param array $raw
     *
     * @return Voucher|static
     */
    public static function fromArray(array $raw)
    {
        $voucher = new static();
        $voucher->issuer = static::getValue($raw, 'issuer');
        $voucher->amount = Amount::fromArray(static::getValue($raw, 'amount', array()));

        return $voucher;
    }

    /**
     * @return string
     */
    public function getIssuer()
    {
        return $this->issuer;
    }

    /**
     * @param string $issuer
     */
    public function setIssuer($issuer)
    {
        $this->issuer = $issuer;
    }

    /**
     * @return Amount
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param Amount $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }
}
