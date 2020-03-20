<?php

namespace Mollie\Bundle\PaymentBundle\Mapper;

use Doctrine\Common\Collections\Collection;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Address;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\Order;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Orders\OrderLine;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Payment;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Configuration\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Provider\SurchargeProvider;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;
use Oro\Bundle\OrderBundle\Entity\Order as OroOrder;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration as LocaleConfiguration;

/**
 * Class MollieDtoMapper
 *
 * @package Mollie\Bundle\PaymentBundle\Provider
 */
class MollieDtoMapper implements MollieDtoMapperInterface
{
    /**
     * @var TaxProviderRegistry
     */
    private $taxProviderRegistry;
    /**
     * @var SurchargeProvider
     */
    private $surchargeProvider;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var LocalizationHelper
     */
    private $localizationHelper;
    /**
     * @var string
     */
    protected $webhooksUrlReplacement;

    /**
     * MollieDtoMapper constructor.
     *
     * @param TaxProviderRegistry $taxProviderRegistry
     * @param SurchargeProvider $surchargeProvider
     * @param TranslatorInterface $translator
     * @param DoctrineHelper $doctrineHelper
     * @param RouterInterface $router
     * @param LocalizationHelper $localizationHelper
     * @param string $webhooksUrlReplacement
     */
    public function __construct(
        TaxProviderRegistry $taxProviderRegistry,
        SurchargeProvider $surchargeProvider,
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper,
        RouterInterface $router,
        LocalizationHelper $localizationHelper,
        $webhooksUrlReplacement = ''
    ) {
        $this->taxProviderRegistry = $taxProviderRegistry;
        $this->surchargeProvider = $surchargeProvider;
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
        $this->router = $router;
        $this->localizationHelper = $localizationHelper;
        $this->webhooksUrlReplacement = $webhooksUrlReplacement;
    }

    /**
     * @inheritDoc
     */
    public function getOrderData(PaymentTransaction $paymentTransaction)
    {
        /** @var OroOrder $order */
        $order = $this->getOrderEntity($paymentTransaction);
        if (!$order) {
            return null;
        }

        $billingAddress = $order->getBillingAddress();
        if (!$billingAddress) {
            return null;
        }

        $orderLines = $order->getLineItems();
        if ($orderLines->isEmpty()) {
            return null;
        }

        $currentLocalization = $this->localizationHelper->getCurrentLocalization();
        $orderData = Order::fromArray([
            'locale' => $currentLocalization ? $currentLocalization->getLanguageCode() : LocaleConfiguration::DEFAULT_LANGUAGE,
            'orderNumber' => $order->getIdentifier(),
            'metadata' => [
                'order_id' => $paymentTransaction->getEntityIdentifier()
            ],
            'amount' => [
                'value' => $paymentTransaction->getAmount(),
                'currency' => $paymentTransaction->getCurrency()
            ],
            'billingAddress' => $this->getAddressData($billingAddress, $order->getEmail())->toArray(),
            'redirectUrl' => $this->ensureDebugWebhookUrl(
                $this->router->generate(
                    'oro_payment_callback_return',
                    ['accessIdentifier' => $paymentTransaction->getAccessIdentifier()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            ),
            'webhookUrl' => $this->ensureDebugWebhookUrl(
                $this->router->generate(
                    'oro_payment_callback_notify',
                    [
                        'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                        'accessToken' => $paymentTransaction->getAccessToken(),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            ),
        ]);

        $orderData->setLines(
            array_merge(
                $this->getOrderLinesData($orderLines),
                $this->getSurcharges($order)
            )
        );

        if ($shippingAddress = $order->getShippingAddress()) {
            $orderData->setShippingAddress($this->getAddressData($shippingAddress, $order->getEmail()));
        }

        if ($frontendOwner = $paymentTransaction->getFrontendOwner()) {
            $consumerDateOfBirth = $frontendOwner->getBirthday();
            if ($consumerDateOfBirth && $consumerDateOfBirth instanceof \DateTime) {
                $orderData->setConsumerDateOfBirth($consumerDateOfBirth);
            }
        }

        return $orderData;
    }

    /**
     * @inheritDoc
     */
    public function getOrderLine(OrderLineItem $orderLine)
    {
        $orderLineData = OrderLine::fromArray([
            'sku' => $orderLine->getProductSku(),
            'metadata' => [
                'order_line_id' => $orderLine->getEntityIdentifier(),
            ],
            'name' => $orderLine->getProductName(),
            'quantity' => $orderLine->getQuantity(),
            'unitPrice' => [
                'value' => (string)round($orderLine->getValue(), 2),
                'currency' => $orderLine->getCurrency()
            ],
            'vatRate' => '0.00',
            'vatAmount' => [
                'value' => '0.00',
                'currency' => $orderLine->getCurrency()
            ],
            'totalAmount' => [
                'value' => (string)round($orderLine->getValue() * $orderLine->getQuantity(), 2),
                'currency' => $orderLine->getCurrency()
            ],
        ]);

        $tax = $this->getTax($orderLine);
        if ($tax && (float)($tax->getRow()->getIncludingTax()) > 0) {
            $this->updateOrderLineDataWithTax($orderLineData, $tax->getRow());
            $orderLineData->getUnitPrice()->setAmountValue($tax->getUnit()->getIncludingTax());
        }

        return $orderLineData;
    }

    /**
     * @inheritDoc
     */
    public function getPaymentData(PaymentTransaction $paymentTransaction)
    {
        $currentLocalization = $this->localizationHelper->getCurrentLocalization();

        $payment =  Payment::fromArray([
            'locale' => $currentLocalization ? $currentLocalization->getLanguageCode() : LocaleConfiguration::DEFAULT_LANGUAGE,
            'amount' => [
                'value' => $paymentTransaction->getAmount(),
                'currency' => $paymentTransaction->getCurrency()
            ],
            'description' => "Order #{$paymentTransaction->getEntityIdentifier()}",
            'metadata' => [
                'order_id' => $paymentTransaction->getEntityIdentifier()
            ],
            'redirectUrl' => $this->ensureDebugWebhookUrl(
                $this->router->generate(
                    'oro_payment_callback_return',
                    ['accessIdentifier' => $paymentTransaction->getAccessIdentifier()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            ),
            'webhookUrl' => $this->ensureDebugWebhookUrl(
                $this->router->generate(
                    'oro_payment_callback_notify',
                    [
                        'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                        'accessToken' => $paymentTransaction->getAccessToken(),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            ),
        ]);

        if (($order = $this->getOrderEntity($paymentTransaction)) && ($shippingAddress = $order->getShippingAddress())) {
            $payment->setShippingAddress($this->getAddressData($shippingAddress, $order->getEmail()));
        }

        return $payment;
    }

    /**
     * @inheritDoc
     */
    public function getAddressData(OrderAddress $address, $email)
    {
        return Address::fromArray([
            'organizationName' => $address->getOrganization(),
            'streetAndNumber' => $address->getStreet(),
            'streetAdditional' => $address->getStreet2(),
            'city' => $address->getCity(),
            'region' => $address->getRegionName(),
            'postalCode' => $address->getPostalCode(),
            'country' => $address->getCountryIso2(),
            'title' => $address->getNamePrefix(),
            'givenName' => $address->getFirstName(),
            'familyName' => $address->getLastName(),
            'email' => $email,
        ]);
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return OroOrder|null
     */
    protected function getOrderEntity(PaymentTransaction $paymentTransaction)
    {
        if ($paymentTransaction->getEntityClass() !== OroOrder::class) {
            return null;
        }

        /** @var OroOrder $order */
        $order = $this->doctrineHelper->getEntityReference(
            $paymentTransaction->getEntityClass(), $paymentTransaction->getEntityIdentifier()
        );

        return $order;
    }

    /**
     * @param Collection|OrderLineItem[] $orderLines
     *
     * @return array
     */
    protected function getOrderLinesData($orderLines)
    {
        $orderLinesData = [];
        foreach ($orderLines as $orderLine) {
            if (!$orderLine->getPrice()) {
                continue;
            }

            $orderLinesData[] = $this->getOrderLine($orderLine);
        }

        return $orderLinesData;
    }

    /**
     * @param OroOrder $order
     *
     * @return array
     */
    protected function getSurcharges(OroOrder $order)
    {
        $orderLinesData = [];
        $surcharge = $this->surchargeProvider->getSurcharges($order);
        if ($surcharge->getShippingAmount() > 0) {
            $orderLineData = OrderLine::fromArray(array(
                'name' => $this->translator->trans('oro.order.subtotals.shipping_cost'),
                'type' => 'shipping_fee',
                'quantity' => 1,
                'unitPrice' => array(
                    'value' => (string)$surcharge->getShippingAmount(),
                    'currency' => $order->getCurrency()
                ),
                'totalAmount' => array(
                    'value' => (string)$surcharge->getShippingAmount(),
                    'currency' => $order->getCurrency()
                ),
                'vatRate' => '0.00',
                'vatAmount' => array(
                    'value' => '0.00',
                    'currency' => $order->getCurrency()
                ),
            ));

            $tax = $this->getTax($order);
            if ($tax && (float)($tax->getShipping()->getIncludingTax()) > 0) {
                $this->updateOrderLineDataWithTax($orderLineData, $tax->getShipping());
            }

            $orderLinesData[] = $orderLineData;
        }

        if ($surcharge->getHandlingAmount() > 0) {
            $orderLinesData[] = OrderLine::fromArray(array(
                'name' => $this->translator->trans('mollie.payment.checkout.subtotals.mollie_payment_surcharge'),
                'type' => 'surcharge',
                'quantity' => 1,
                'unitPrice' => array(
                    'value' => (string)$surcharge->getHandlingAmount(),
                    'currency' => $order->getCurrency()
                ),
                'totalAmount' => array(
                    'value' => (string)$surcharge->getHandlingAmount(),
                    'currency' => $order->getCurrency()
                ),
                'vatRate' => '0.00',
                'vatAmount' => array(
                    'value' => '0.00',
                    'currency' => $order->getCurrency()
                ),
            ));
        }

        if (abs($surcharge->getDiscountAmount()) > 0) {
            $orderLinesData[] = OrderLine::fromArray(array(
                'name' => $this->translator->trans('oro.order.subtotals.discount'),
                'type' => 'discount',
                'quantity' => 1,
                'unitPrice' => array(
                    'value' => (string)$surcharge->getDiscountAmount(),
                    'currency' => $order->getCurrency()
                ),
                'totalAmount' => array(
                    'value' => (string)$surcharge->getDiscountAmount(),
                    'currency' => $order->getCurrency()
                ),
                'vatRate' => '0.00',
                'vatAmount' => array(
                    'value' => '0.00',
                    'currency' => $order->getCurrency()
                ),
            ));
        }

        return $orderLinesData;
    }

    /**
     * Gets tax row model for a given order line
     *
     * @param OrderLineItem|OroOrder $orderLine
     *
     * @return Result|null
     */
    protected function getTax($orderLine)
    {
        try {
            $tax = $this->taxProviderRegistry->getEnabledProvider()->getTax($orderLine);
        } catch (TaxationDisabledException $e) {
            $tax = null;
        }

        return $tax;
    }

    /**
     * @param OrderLine $orderLineData
     * @param ResultElement $taxesRow
     */
    protected function updateOrderLineDataWithTax(OrderLine $orderLineData, ResultElement $taxesRow)
    {
        if ($taxesRow->getIncludingTax() === null) {
            return;
        }

        $orderLineData->getUnitPrice()->setAmountValue($taxesRow->getIncludingTax());
        $orderLineData->getTotalAmount()->setAmountValue($taxesRow->getIncludingTax());
        $orderLineData->getVatAmount()->setAmountValue($taxesRow->getTaxAmount());
        if ((float)($taxesRow->getExcludingTax()) > 0) {
            $orderLineData->setVatRate(
                round(100 * (float)($taxesRow->getTaxAmount()) / (float)($taxesRow->getExcludingTax()), 2)
            );
        }
    }

    /**
     * Replaces schema and host from the provided url with debug url set in configuration. Used for development to set
     * tunneling (ngrok) url for webhooks.
     *
     * @param string $url
     *
     * @return string
     */
    protected function ensureDebugWebhookUrl($url)
    {
        /** @var Configuration $configuration */
        $configuration = ServiceRegister::getService(Configuration::CLASS_NAME);
        if (empty($this->webhooksUrlReplacement) || !$configuration->isDebugModeEnabled()) {
            return $url;
        }

        return preg_replace(
            '/http(s)?:\/\/[^\/]*\//i',
            rtrim($this->webhooksUrlReplacement, '/') . '/',
            $url
        );
    }
}
