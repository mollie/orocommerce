<?php

namespace Mollie\Bundle\PaymentBundle\Form\Type;

use Mollie\Bundle\PaymentBundle\Entity\ChannelSettings;
use Mollie\Bundle\PaymentBundle\Form\EventListener\ChannelSettingsTypeSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Form type for Mollie integration settings
 */
class ChannelSettingsType extends AbstractType
{
    const BLOCK_PREFIX = 'mollie_channel_setting_type';

    /** @var ChannelSettingsTypeSubscriber */
    protected $channelSettingsTypeSubscriber;

    /**
     * @param ChannelSettingsTypeSubscriber $channelSettingsTypeSubscriber
     */
    public function __construct(ChannelSettingsTypeSubscriber $channelSettingsTypeSubscriber)
    {
        $this->channelSettingsTypeSubscriber = $channelSettingsTypeSubscriber;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('mollieSaveMarker', HiddenType::class)
            ->add('formRefreshRequired', HiddenType::class, [
                'mapped' => false
            ])
            ->add('isTokenOnlySubmit', HiddenType::class, [
                'mapped' => false
            ])
            ->add('mollieVersion', HiddenType::class, [
                'mapped' => false
            ])
            ->add(
                'authToken',
                TextType::class,
                [
                    'label' => 'mollie.payment.config.authorization.auth_token.label',
                    'required' => true,
                    'attr' => ['autocomplete' => 'off'],
                    'constraints' => [
                        new NotBlank(),
                    ],
                ]
            )
            ->add(
                'testMode',
                CheckboxType::class,
                [
                    'label' => 'mollie.payment.config.authorization.test_mode.label',
                    'required' => false,
                ]
            );

        $builder->addEventSubscriber($this->channelSettingsTypeSubscriber);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => ChannelSettings::class,
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
