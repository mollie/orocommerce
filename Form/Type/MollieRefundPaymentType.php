<?php

namespace Mollie\Bundle\PaymentBundle\Form\Type;

use Mollie\Bundle\PaymentBundle\Form\Entity\MollieRefund;
use Mollie\Bundle\PaymentBundle\Form\Entity\MollieRefundPayment;
use Mollie\Bundle\PaymentBundle\Manager\MollieRefundProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class MollieRefundPaymentType
 *
 * @package Mollie\Bundle\PaymentBundle\Form\Type
 */
class MollieRefundPaymentType extends AbstractType
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
            ->add('amount', NumberType::class, [
                'label' => 'mollie.payment.refund.amountToRefund',
                'constraints' => [new NotBlank()],
            ])
            ->add('description', TextType::class, [
                'label' => 'mollie.payment.refund.description',
                'required' => false,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => MollieRefundPayment::class,
                'validation_groups' => function (FormInterface $form) {
                    if ($form->getName() === 'refundPayment') {
                        $mollieRefundForm = $form->getParent();
                        if ($mollieRefundForm) {
                            /** @var MollieRefund $mollieRefund */
                            $mollieRefund = $mollieRefundForm->getData();
                            if ($mollieRefund && $mollieRefund->getSelectedTab() === MollieRefundProvider::ORDER_LINE_REFUND) {
                                return false;
                            }
                        }
                    }

                    return ['Default'];
                }
            ]
        );
    }
}
