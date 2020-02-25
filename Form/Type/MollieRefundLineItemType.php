<?php

namespace Mollie\Bundle\PaymentBundle\Form\Type;

use Mollie\Bundle\PaymentBundle\Form\Entity\MollieRefund;
use Mollie\Bundle\PaymentBundle\Form\Entity\MollieRefundLineItem;
use Mollie\Bundle\PaymentBundle\Manager\MollieRefundProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Class MollieRefundLineItemType
 *
 * @package Mollie\Bundle\PaymentBundle\Form\Type
 */
class MollieRefundLineItemType extends AbstractType
{
    /**
     * @inheritDoc
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('sku', HiddenType::class)
            ->add('mollieId', HiddenType::class)
            ->add('product', HiddenType::class)
            ->add('orderedQuantity', HiddenType::class)
            ->add('refundedQuantity', HiddenType::class)
            ->add('isRefundable', HiddenType::class)
            ->add('price', HiddenType::class);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
    }

    public function onPreSetData(FormEvent $event)
    {
        /** @var MollieRefundLineItem|null $refundLineItem */
        $refundLineItem = $event->getData();
        if (!$refundLineItem) {
            return;
        }

        if ($refundLineItem->getOrderedQuantity() === null || $refundLineItem->getRefundedQuantity() === null) {
            return;
        }

        $refundableQuantity = $refundLineItem->getOrderedQuantity() - $refundLineItem->getRefundedQuantity();

        $event->getForm()->add('quantityToRefund',
            IntegerType::class,
            [
                'required' => false,
                'label' => ' ',
                'constraints' => [new Range([
                    'min' => 0,
                    'max' => $refundableQuantity,
                ])],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => MollieRefundLineItem::class,
                'validation_groups' => function (FormInterface $form) {
                    $mollieRefundForm = $this->getMollieRefundForm($form);
                    if ($mollieRefundForm) {
                        /** @var MollieRefund $mollieRefund */
                        $mollieRefund = $mollieRefundForm->getData();
                        if ($mollieRefund && $mollieRefund->getSelectedTab() === MollieRefundProvider::PAYMENT_REFUND) {
                            return false;
                        }
                    }

                    return ['Default'];
                }
            ]
        );
    }

    /**
     * @param FormInterface $form
     *
     * @return FormInterface|null
     */
    private function getMollieRefundForm(FormInterface $form)
    {
        $collectionItems = $form->getParent();
        if ($collectionItems && $collectionItems->getName() === 'refundItems') {
            return $collectionItems->getParent();
        }

        return null;
    }
}
