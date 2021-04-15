<?php

namespace Mollie\Bundle\PaymentBundle\Entity\GeneratorExtension;

use Oro\Component\PhpUtils\ClassGenerator;
use Mollie\Bundle\PaymentBundle\Entity\MollieSurchargeAwareInterface;
use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\AbstractEntityGeneratorExtension;

/**
 * Add MollieSurchargeAwareInterface interface to Entities that has mollie surcharge amount fields
 *
 * Class MollieSurchargeAwareEntityGeneratorExtension
 *
 * @package Mollie\Bundle\PaymentBundle\Entity\GeneratorExtension
 */
class MollieSurchargeAwareEntityGeneratorExtension extends AbstractEntityGeneratorExtension
{
    /**
     * @var array
     */
    private $supportedEntities = [];

    /**
     * @param string $entityClassName
     */
    public function registerSupportedEntity($entityClassName)
    {
        $this->supportedEntities[$entityClassName] = true;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(array $schema): bool
    {
        return !empty($this->supportedEntities[$schema['class']]);
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $schema, ClassGenerator $class): void
    {
        if ($class->hasProperty('mollie_surcharge_amount')) {
            $class->addImplement(MollieSurchargeAwareInterface::class);
        }
    }
}
