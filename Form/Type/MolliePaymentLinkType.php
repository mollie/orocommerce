<?php

namespace Mollie\Bundle\PaymentBundle\Form\Type;

use Mollie\Bundle\PaymentBundle\Form\Entity\MolliePaymentLink;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class MolliePaymentLinkType
 *
 * @package Mollie\Bundle\PaymentBundle\Form\Type
 */
class MolliePaymentLinkType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     *
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'paymentLink',
                TextType::class,
                [
                    'attr' => [
                        'readonly' => true,
                    ],
                ]
            )
            ->add('isPaymentsApiOnly', HiddenType::class)
            ->add('isMolliePaymentOnOrder', HiddenType::class)
            ->add('paymentMethods', HiddenType::class)
            ->add('selectedPaymentMethods', HiddenType::class);

        $builder->get('paymentMethods')
            ->addModelTransformer(new CallbackTransformer(
                function ($paymentMethodsAsArray) {
                    // transform the array to a string
                    return json_encode($paymentMethodsAsArray);
                },
                function ($paymentMethodsAsString) {
                    // transform the string back to an array
                    return json_decode($paymentMethodsAsString, true);
                }
            ));
        $builder->get('selectedPaymentMethods')
            ->addModelTransformer(new CallbackTransformer(
                function ($paymentMethodsAsArray) {
                    // transform the array to a string
                    return implode(',', $paymentMethodsAsArray);
                },
                function ($paymentMethodsAsString) {
                    // transform the string back to an array
                    return !empty($paymentMethodsAsString) ? explode(',', $paymentMethodsAsString) : [];
                }
            ));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => MolliePaymentLink::class,
            ]
        );
    }
}
