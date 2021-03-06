<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO;

/**
 * Class Link
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO
 */
class Link extends BaseDto
{
    /**
     * @var string
     */
    protected $href;
    /**
     * @var string
     */
    protected $type;
    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $raw)
    {
        $result = new static();

        $result->href = static::getValue($raw, 'href');
        $result->type = static::getValue($raw, 'type');

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return array(
            'href' => $this->href,
            'type' => $this->type,
        );
    }

    /**
     * @return string
     */
    public function getHref()
    {
        return $this->href;
    }

    /**
     * @param string $href
     */
    public function setHref($href)
    {
        $this->href = $href;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }
}
