<?php

namespace Mollie\Bundle\PaymentBundle\Form\Type;

use Mollie\Bundle\PaymentBundle\Form\Entity\MollieRefund;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class MollieRefundType
 *
 * @package Mollie\Bundle\PaymentBundle\Form\Type
 */
class MollieRefundType extends AbstractType
{
    const NAME = 'oro_order_refund_widget';
    /**
     * {@inheritdoc}
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('isOrderApiUsed', HiddenType::class)
            ->add('refundPayment', MollieRefundPaymentType::class)
            ->add('selectedTab', HiddenType::class)
            ->add('totalRefunded', HiddenType::class)
            ->add('isOrderRefundable', HiddenType::class)
            ->add('totalValue', HiddenType::class)
            ->add('currency', TextType::class, [
                'required' => false,
                'attr' => [
                    'readonly' => true
                ],
            ])
            ->add('currencySymbol', HiddenType::class)
            ->add('refundItems', CollectionType::class, [
                'entry_type' => MollieRefundLineItemType::class,
                'entry_options' => ['label' => false],
                'label' =>  false,
                'by_reference' => false,
                'required' => false,
            ]);
    }

    /**
     * {@inheritdoc}
     *
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['required'] = false;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => MollieRefund::class,
                'show_form_when_empty' => true,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
