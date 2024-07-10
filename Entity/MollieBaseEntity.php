<?php

namespace Mollie\Bundle\PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class MollieBaseEntity Entity with settings for base Mollie ORM models
 * @package Mollie\Bundle\PaymentBundle\Entity
 */
#[ORM\Entity]
#[ORM\Table(name: 'mollie_entity')]
#[ORM\Index(name: 'mollie_entity_type_idx', columns: ['type'])]
#[ORM\Index(name: 'mollie_entity_type_index1_idx', columns: ['type', 'index1'])]
#[ORM\Index(name: 'mollie_entity_type_index2_idx', columns: ['type', 'index2'])]
#[ORM\Index(name: 'mollie_entity_type_index3_idx', columns: ['type', 'index3'])]
#[ORM\Index(name: 'mollie_entity_type_index4_idx', columns: ['type', 'index4'])]
#[ORM\Index(name: 'mollie_entity_type_index5_idx', columns: ['type', 'index5'])]
#[ORM\Index(name: 'mollie_entity_type_index6_idx', columns: ['type', 'index6'])]
#[ORM\Index(name: 'mollie_entity_type_index7_idx', columns: ['type', 'index7'])]
#[ORM\Index(name: 'mollie_entity_type_context_index1_idx', columns: ['type', 'context', 'index1'])]
#[ORM\Index(name: 'mollie_entity_type_context_index2_idx', columns: ['type', 'context', 'index2'])]
#[ORM\Index(name: 'mollie_entity_type_context_index3_idx', columns: ['type', 'context', 'index3'])]
#[ORM\Index(name: 'mollie_entity_type_context_index4_idx', columns: ['type', 'context', 'index4'])]
#[ORM\Index(name: 'mollie_entity_type_context_index5_idx', columns: ['type', 'context', 'index5'])]
#[ORM\Index(name: 'mollie_entity_type_context_index6_idx', columns: ['type', 'context', 'index6'])]
#[ORM\Index(name: 'mollie_entity_type_context_index7_idx', columns: ['type', 'context', 'index7'])]
class MollieBaseEntity
{
    /**
     * @var integer $id
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    protected $id;
    /**
     * @var string $type
     */
    #[ORM\Column(name: 'type', type: 'string', length: 128, nullable: false)]
    protected $type;
    /**
     * @var string $context
     */
    #[ORM\Column(name: 'context', type: 'string', length: 128, nullable: true)]
    protected $context;
    /**
     * @var string $index1
     */
    #[ORM\Column(name: 'index1', type: 'string', length: 255, nullable: true)]
    protected $index1;
    /**
     * @var string $index2
     */
    #[ORM\Column(name: 'index2', type: 'string', length: 255, nullable: true)]
    protected $index2;
    /**
     * @var string $index3
     */
    #[ORM\Column(name: 'index3', type: 'string', length: 255, nullable: true)]
    protected $index3;
    /**
     * @var string $index4
     */
    #[ORM\Column(name: 'index4', type: 'string', length: 255, nullable: true)]
    protected $index4;
    /**
     * @var string $index5
     */
    #[ORM\Column(name: 'index5', type: 'string', length: 255, nullable: true)]
    protected $index5;
    /**
     * @var string $index6
     */
    #[ORM\Column(name: 'index6', type: 'string', length: 255, nullable: true)]
    protected $index6;
    /**
     * @var string $index7
     */
    #[ORM\Column(name: 'index7', type: 'string', length: 255, nullable: true)]
    protected $index7;
    /**
     * @var string $data
     */
    #[ORM\Column(name: 'data', type: 'text', nullable: false)]
    protected $data;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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

    /**
     * @return string
     */
    public function getIndex1()
    {
        return $this->index1;
    }

    /**
     * @param string $index1
     */
    public function setIndex1($index1)
    {
        $this->index1 = $index1;
    }

    /**
     * @return string
     */
    public function getIndex2()
    {
        return $this->index2;
    }

    /**
     * @param string $index2
     */
    public function setIndex2($index2)
    {
        $this->index2 = $index2;
    }

    /**
     * @return string
     */
    public function getIndex3()
    {
        return $this->index3;
    }

    /**
     * @param string $index3
     */
    public function setIndex3($index3)
    {
        $this->index3 = $index3;
    }

    /**
     * @return string
     */
    public function getIndex4()
    {
        return $this->index4;
    }

    /**
     * @param string $index4
     */
    public function setIndex4($index4)
    {
        $this->index4 = $index4;
    }

    /**
     * @return string
     */
    public function getIndex5()
    {
        return $this->index5;
    }

    /**
     * @param string $index5
     */
    public function setIndex5($index5)
    {
        $this->index5 = $index5;
    }

    /**
     * @return string
     */
    public function getIndex6()
    {
        return $this->index6;
    }

    /**
     * @param string $index6
     */
    public function setIndex6($index6)
    {
        $this->index6 = $index6;
    }

    /**
     * @return string
     */
    public function getIndex7()
    {
        return $this->index7;
    }

    /**
     * @param string $index7
     */
    public function setIndex7($index7)
    {
        $this->index7 = $index7;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param string $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }
}
