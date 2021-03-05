<?php

namespace Mollie\Bundle\PaymentBundle\Form\Type;

use Mollie\Bundle\PaymentBundle\Entity\PaymentMethodSettings;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\PaymentMethods;
use Oro\Bundle\ApiBundle\Form\Type\NumberType;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Form type for Mollie integration payment methods settings
 */
class PaymentMethodSettingsType extends AbstractType
{
    const BLOCK_PREFIX = 'mollie_payment_method_setting_type';
    const DEFAULT_TRANSACTION_DESCRIPTION = '{orderNumber}';

    const ALIAS = 'product';

    /**
     * @var \Symfony\Contracts\Translation\TranslatorInterface
     */
    private $translator;

    /**
     * @var EntityAliasResolver
     */
    private $aliasResolver;

    /**
     * @var ConfigModelManager
     */
    private $configModelManager;


    /**
     * PaymentMethodSettingsType constructor.
     *
     * @param TranslatorInterface $translator
     * @param EntityAliasResolver $aliasResolver
     * @param ConfigModelManager $configModelManager
     */
    public function __construct(
        TranslatorInterface $translator,
        EntityAliasResolver $aliasResolver,
        ConfigModelManager $configModelManager
    ) {
        $this->translator = $translator;
        $this->configModelManager = $configModelManager;
        $this->aliasResolver = $aliasResolver;
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
                'paymentDescriptions',
                LocalizedFallbackValueCollectionType::class,
                [
                    'label' => 'mollie.payment.config.payment_methods.payment.description.label',
                    'tooltip' => 'mollie.payment.config.payment_methods.payment.description.tooltip.label',
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
        $productAttributes = $this->getProductAttributes();

        /** @var PaymentMethodSettings|null $paymentMethodSetting */
        $paymentMethodSetting = $event->getData();
        if (!$paymentMethodSetting) {
            return;
        }

        $paymentMethodConfig = $paymentMethodSetting->getPaymentMethodConfig();
        if (!$paymentMethodConfig) {
            return;
        }

        $surchargeTooltip = 'mollie.payment.config.payment_methods.surcharge.tooltip';
        if ($paymentMethodConfig->isSurchargeRestricted()) {
            $surchargeTooltip = 'mollie.payment.config.payment_methods.surcharge.klarna_tooltip';
        }

        $orderExpiryDaysTooltip = $paymentMethodConfig->isApiMethodRestricted() ?
            'mollie.payment.config.payment_methods.orderExpiryDays.klarna_tooltip' :
            'mollie.payment.config.payment_methods.orderExpiryDays.tooltip';

        $event->getForm()->add(
            'surcharge',
            NumberType::class,
            [
                'label' => 'mollie.payment.config.payment_methods.surcharge.label',
                'tooltip' => $surchargeTooltip,
                'required' => false,
                'attr' => ['autocomplete' => 'off'],
                'constraints' => [
                    new Type(['type' => 'numeric'])
                ],
            ]
        )->add(
            'orderExpiryDays',
            IntegerType::class,
            [
                'label' => 'mollie.payment.config.payment_methods.orderExpiryDays.label',
                'tooltip' => $orderExpiryDaysTooltip,
                'required' => false,
            ]
        );

        if ($paymentMethodConfig->getOriginalAPIConfig()->getId() === 'banktransfer') {
            $event->getForm()->add(
                'paymentExpiryDays',
                IntegerType::class,
                [
                    'label' => 'mollie.payment.config.payment_methods.paymentExpiryDays.label',
                    'tooltip' => 'mollie.payment.config.payment_methods.paymentExpiryDays.tooltip',
                    'required' => false,
                ]
            );
        }

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
                    'attr' => [
                        'class' => 'mollie-method-select',
                        'data-method-wrapper' => $paymentMethodConfig->getMollieId(),
                    ],
                ]
            );
        }

        $event->getForm()->add(
            'transactionDescriptions',
            LocalizedFallbackValueCollectionType::class,
            [
                'label' => 'mollie.payment.config.payment_methods.transactionDescription.label',
                'tooltip' => 'mollie.payment.config.payment_methods.transactionDescription.tooltip',
                'required' => true,
                'entry_options' => ['constraints' => [new NotBlank()]],
            ]
        );

        if ($paymentMethodConfig->isMollieComponentsSupported()) {
            $event->getForm()->add(
                'mollieComponents',
                CheckboxType::class,
                [
                    'value' => 1,
                    'label' => 'mollie.payment.config.payment_methods.mollie_components.label',
                    'tooltip' => 'mollie.payment.config.payment_methods.mollie_components.tooltip',
                    'required' => true,
                ]
            );
        }

        if ($paymentMethodConfig->isIssuerListSupported()) {
            $issuerListStyleOptions = [
                'mollie.payment.config.payment_methods.issuer_list.option.list' => PaymentMethodConfig::ISSUER_LIST,
                'mollie.payment.config.payment_methods.issuer_list.option.dropdown' => PaymentMethodConfig::ISSUER_DROPDOWN,
            ];
            $event->getForm()->add(
                'issuerListStyle',
                ChoiceType::class,
                [
                    'choices' => $issuerListStyleOptions,
                    'label' => 'mollie.payment.config.payment_methods.issuer_list.label',
                    'tooltip' => 'mollie.payment.config.payment_methods.issuer_list.tooltip',
                    'required' => true,
                    'placeholder' => false,
                ]
            );
        }

        if ($paymentMethodConfig->getMollieId() === PaymentMethods::Vouchers) {
            $event->getForm()->add(
                'voucherCategory',
                ChoiceType::class,
                [
                    'choices' => [
                        'mollie.payment.config.payment_methods.category.choice.none' => PaymentMethodConfig::VOUCHER_CATEGORY_NONE,
                        'mollie.payment.config.payment_methods.category.choice.meal' => PaymentMethodConfig::VOUCHER_CATEGORY_MEAL,
                        'mollie.payment.config.payment_methods.category.choice.eco' => PaymentMethodConfig::VOUCHER_CATEGORY_ECO,
                        'mollie.payment.config.payment_methods.category.choice.gift' => PaymentMethodConfig::VOUCHER_CATEGORY_GIFT,
                        'mollie.payment.config.payment_methods.category.choice.custom' => PaymentMethodConfig::VOUCHER_CATEGORY_CUSTOM,
                    ],
                    'label' => 'mollie.payment.config.payment_methods.category.label',
                    'required' => true,
                    'placeholder' => false,
                    'tooltip' => 'mollie.payment.config.payment_methods.category.tooltip',
                ]
            );
        }
        if ($paymentMethodConfig->getMollieId() === PaymentMethods::Vouchers) {
            $event->getForm()->add(
                'productAttribute',
                ChoiceType::class,
                [
                    'choices' => $productAttributes,
                    'label' => 'mollie.payment.config.payment_methods.attribute.label',
                    'required' => true,
                    'placeholder' => false,
                    'tooltip' => 'mollie.payment.config.payment_methods.attribute.tooltip',
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

    public function getProductAttributes()
    {
        $entityConfigModel = $this->getEntityProduct();
        $labelArray = [];
        /** @var FieldConfigModel $fieldConfigModel */
        $fields = $entityConfigModel->getFields(
            function (FieldConfigModel $configModel) use ($labelArray) {
                $isAttribute = $configModel->toArray('attribute')['is_attribute'];
                $type = $configModel->getType();
                if ($isAttribute && ($type === 'string' || $type === 'enum')) {
                    $labelArray[] = $configModel->toArray('entity')['label'];
                }

                return $labelArray;
            }
        );

        $labelArray = [];
        $labelArrayReturn = [];
        foreach ($fields as $field) {
            $label = $field->toArray('entity')['label'];
            $name = $field->getFieldName();
            if ($label === 'oro.product.voucher_category.label') {
                $labelArrayReturn['Voucher Category'] = $name;
            } else {
                $labelArray[$this->translator->trans($label)] = $name;
            }
        }

        return $this->createProductAttributeArray($labelArray, $labelArrayReturn);
    }

    /**
     * @param string $alias
     *
     * @return EntityConfigModel
     */
    public function getEntityProduct()
    {
        $entityClass = $this->aliasResolver->getClassByAlias(self::ALIAS);

        return $this->configModelManager->findEntityModel($entityClass);
    }

    private function createProductAttributeArray(array $labelArray, array $returnArray)
    {
        foreach ($labelArray as $label) {
            $index = array_search($label, $labelArray);
            $returnArray[$index] = $label;
        }

        return $returnArray;
    }
}
