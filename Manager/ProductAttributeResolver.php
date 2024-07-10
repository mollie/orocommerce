<?php


namespace Mollie\Bundle\PaymentBundle\Manager;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Class ProductAttributeResolver
 *
 * @package Mollie\Bundle\PaymentBundle\Manager
 */
class ProductAttributeResolver
{
    /**
     * @var Product
     */
    protected $product;
    /**
     * @var string
     */
    protected $fallbackAttribute;
    /**
     * @var string
     */
    protected $productProperty;

    /**
     * ProductAttributeProvider constructor.
     *
     * @param Product $product
     * @param string $fallbackAttribute
     * @param string $productProperty
     */
    public function __construct(Product $product, $fallbackAttribute, $productProperty)
    {
        $this->product = $product;
        $this->fallbackAttribute = $fallbackAttribute;
        $this->productProperty = $productProperty;
    }

    /**
     * Returns attribute value, based on the configuration
     *
     * @return string|null
     */
    public function getPropertyValue()
    {
        $methodName = $this->getPropertyGetterName();
        if ($methodName) {
            $category = $this->product->{$methodName}();
            $categoryValue = $this->getCategoryValue($category);
            if (in_array($categoryValue, ['meal', 'eco', 'gift'], true)) {
                return $categoryValue;
            }
        }

        return $this->fallbackAttribute !== PaymentMethodConfig::VOUCHER_CATEGORY_NONE ?
            $this->fallbackAttribute : null;
    }

    /**
     * @param string|object $category
     *
     * @return string|null
     */
    protected function getCategoryValue($category)
    {
        if (is_object($category) && \Oro\Bundle\EntityExtendBundle\EntityPropertyInfo::methodExists($category, 'getId')) {
            return $category->getId();
        }

        return is_string($category) ? $category : null;
    }

    /**
     * Returns method name for getting product attribute
     *
     * @return string|null
     */
    protected function getPropertyGetterName()
    {
        if (\Oro\Bundle\EntityExtendBundle\EntityPropertyInfo::methodExists($this->product, $this->productProperty)) {
            return $this->productProperty;
        }

        if (\Oro\Bundle\EntityExtendBundle\EntityPropertyInfo::methodExists($this->product, "get$this->productProperty")) {
            return "get$this->productProperty";
        }

        if (\Oro\Bundle\EntityExtendBundle\EntityPropertyInfo::methodExists($this->product, 'get' . MethodNameGenerator::fromSnakeCase($this->productProperty))) {
            return 'get' . MethodNameGenerator::fromSnakeCase($this->productProperty);
        }

        if (\Oro\Bundle\EntityExtendBundle\EntityPropertyInfo::methodExists($this->product, 'get' . MethodNameGenerator::fromSnakeCase($this->productProperty, false))) {
            return 'get' . MethodNameGenerator::fromSnakeCase($this->productProperty, false);
        }

        return null;
    }
}
