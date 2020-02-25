<?php

namespace Mollie\Bundle\PaymentBundle\Provider;

use Mollie\Bundle\PaymentBundle\Entity\MollieSurchargeAwareInterface;
use Oro\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutToOrderConverter;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\AbstractSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\SubtotalProviderConstructorArguments;
use Symfony\Contracts\Translation\TranslatorInterface;

class MollieSurchargeProvider extends AbstractSubtotalProvider implements SubtotalProviderInterface
{
    const TYPE = 'mollie_payment_surcharge';
    const NAME = 'mollie_payment.subtotal_payment_surcharge';
    const SUBTOTAL_SORT_ORDER = 250;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var RoundingServiceInterface
     */
    protected $rounding;
    /**
     * @var \Oro\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutToOrderConverter
     */
    private $orderConverter;

    /**
     * @param TranslatorInterface $translator
     * @param RoundingServiceInterface $rounding
     * @param SubtotalProviderConstructorArguments $arguments
     */
    public function __construct(
        TranslatorInterface $translator,
        RoundingServiceInterface $rounding,
        SubtotalProviderConstructorArguments $arguments,
        CheckoutToOrderConverter $orderConverter
    ) {
        parent::__construct($arguments);

        $this->translator = $translator;
        $this->rounding = $rounding;
        $this->orderConverter = $orderConverter;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param MollieSurchargeAwareInterface $entity
     *
     * @return Subtotal
     * @throws \Oro\Bundle\CurrencyBundle\Exception\InvalidRoundingTypeException
     */
    public function getSubtotal($entity)
    {
        if (!$this->isSupported($entity)) {
            throw new \InvalidArgumentException('Entity not supported for provider');
        }

        $subtotal = new Subtotal();

        $subtotal->setType(self::TYPE);
        $subtotal->setSortOrder(self::SUBTOTAL_SORT_ORDER);
        $translation = 'mollie.payment.checkout.subtotals.' . self::TYPE;
        $subtotal->setLabel($this->translator->trans($translation));
        $subtotal->setVisible((bool) $entity->getMollieSurchargeAmount());
        $subtotal->setCurrency($this->getBaseCurrency($entity));

        $subtotalAmount = 0.0;
        if ($entity->getMollieSurchargeAmount() !== null) {
            $subtotalAmount = $entity->getMollieSurchargeAmount();
        }

        $subtotal->setAmount($this->rounding->round($subtotalAmount));

        return $subtotal;
    }

    /**
     * @inheritDoc
     */
    public function isSupported($entity)
    {
        // return $entity instanceof Checkout;
        return $entity instanceof MollieSurchargeAwareInterface;
    }
}