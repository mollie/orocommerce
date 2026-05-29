<?php

namespace Mollie\Bundle\PaymentBundle\Form\Type;

use Mollie\Bundle\PaymentBundle\Form\Entity\MollieCapture;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class MollieCaptureType extends AbstractType
{
    const NAME = 'oro_order_capture_widget';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('amount', NumberType::class, [
                'label' => 'mollie.payment.capture.amount.label',
                'constraints' => [new NotBlank(), new Positive()],
            ])
            ->add('currency', TextType::class, [
                'required' => false,
                'attr' => ['readonly' => true],
            ])
            ->add('currencySymbol', HiddenType::class)
            ->add('totalAuthorized', HiddenType::class)
            ->add('totalCaptured', HiddenType::class)
            ->add('totalValue', HiddenType::class);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['required'] = false;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => MollieCapture::class,
            'show_form_when_empty' => true,
        ]);
    }

    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
