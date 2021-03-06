<?php

namespace Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO;

/**
 * Class WebsiteProfile
 *
 * @package Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO
 */
class WebsiteProfile extends BaseDto
{
    /**
     * @var string
     */
    protected $resource;
    /**
     * @var string
     */
    protected $id;
    /**
     * @var string
     */
    protected $name;

    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $raw)
    {
        $result = new static();

        $result->resource = static::getValue($raw, 'resource');
        $result->id = static::getValue($raw, 'id');
        $result->name = static::getValue($raw, 'name');

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return array(
            'resource' => $this->resource,
            'id' => $this->id,
            'name' => $this->name,
        );
    }

    /**
     * @return string
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @param string $resource
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
