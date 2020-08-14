<?php

namespace Mollie\Bundle\PaymentBundle\Form\Type;

use Mollie\Bundle\PaymentBundle\Entity\PaymentMethodSettings;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Oro\Bundle\ApiBundle\Form\Type\NumberType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * Form type for Mollie integration payment methods settings
 */
class PaymentMethodSettingsType extends AbstractType
{
    const BLOCK_PREFIX = 'mollie_payment_method_setting_type';

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * PaymentMethodSettingsType constructor.
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('mollieMethodId', HiddenType::class)
            ->add('mollieMethodDescription', HiddenType::class)
            ->add('enabled', HiddenType::class)
            ->add('imagePath', HiddenType::class)
            ->add('originalImagePath', HiddenType::class)
            ->add(
                'names',
                LocalizedFallbackValueCollectionType::class,
                [
                    'label' => 'mollie.payment.config.payment_methods.name.label',
                    'required' => true,
                    'entry_options' => ['constraints' => [new NotBlank()]],
                ]
            )
            ->add(
                'descriptions',
                LocalizedFallbackValueCollectionType::class,
                [
                    'label' => 'mollie.payment.config.payment_methods.description.label',
                    'required' => true,
                    'entry_options' => ['constraints' => [new NotBlank()]],
                ]
            )
            ->add(
                'image',
                FileType::class,
                [
                    'label' => 'mollie.payment.config.payment_methods.image.label',
                    'required' => false,
                    'constraints' => [
                        new Image([
                            'maxSize' => '2M',
                            'mimeTypesMessage' => $this->translator->trans(
                                'mollie.payment.config.payment_methods.image.mime_type_error'
                            ),
                        ]),
                    ],
                ]
            );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event)
    {
        /** @var PaymentMethodSettings|null $paymentMethodSetting */
        $paymentMethodSetting = $event->getData();
        if (!$paymentMethodSetting) {
            return;
        }

        $paymentMethodConfig = $paymentMethodSetting->getPaymentMethodConfig();
        if (!$paymentMethodConfig) {
            return;
        }

        $tooltip = 'mollie.payment.config.payment_methods.surcharge.tooltip';
        if ($paymentMethodConfig->isSurchargeRestricted()) {
            $tooltip = 'mollie.payment.config.payment_methods.surcharge.klarna_tooltip';
        }

        $event->getForm()->add(
            'surcharge',
            NumberType::class,
            [
                'label' => 'mollie.payment.config.payment_methods.surcharge.label',
                'tooltip' => $tooltip,
                'required' => false,
                'attr' => ['autocomplete' => 'off'],
                'constraints' => [
                    new Type(['type' => 'numeric'])
                ],
            ]
        );

        if (!$paymentMethodConfig->isApiMethodRestricted()) {
            $paymentMethodApiChoices = [
                'mollie.payment.config.payment_methods.method.option.payment_api.label' => PaymentMethodConfig::API_METHOD_PAYMENT,
                'mollie.payment.config.payment_methods.method.option.order_api.label' => PaymentMethodConfig::API_METHOD_ORDERS,
            ];
            $event->getForm()->add(
                'method',
                ChoiceType::class,
                [
                    'choices' => $paymentMethodApiChoices,
                    'label' => 'mollie.payment.config.payment_methods.method.label',
                    'tooltip' => 'mollie.payment.config.payment_methods.method.tooltip',
                    'required' => true,
                    'placeholder' => false,
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => PaymentMethodSettings::class,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::BLOCK_PREFIX;
    }
}
