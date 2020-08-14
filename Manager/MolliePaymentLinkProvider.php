<?php

namespace Mollie\Bundle\PaymentBundle\Manager;

use Mollie\Bundle\PaymentBundle\Entity\PaymentLinkMethod;
use Mollie\Bundle\PaymentBundle\Form\Entity\MolliePaymentLink;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\PaymentMethod\Model\PaymentMethodConfig;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\Logger;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\QueryFilter\Operators;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\QueryFilter\QueryFilter;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ORM\RepositoryRegistry;
use Mollie\Bundle\PaymentBundle\PaymentMethod\Config\MolliePaymentConfigInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProviderInterface;
use Oro\Bundle\WebsiteBundle\Resolver\WebsiteUrlResolver;
use Symfony\Component\Form\Form;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class MolliePaymentLinkProvider
 *
 * @package Mollie\Bundle\PaymentBundle\Manager
 */
class MolliePaymentLinkProvider implements PaymentLinkConfigProviderInterface
{
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;
    /**
     * @var OroPaymentMethodUtility
     */
    private $paymentMethodUtility;
    /**
     * @var PaymentStatusProviderInterface
     */
    private $paymentStatusProvider;
    /**
     * @var WebsiteUrlResolver
     */
    private $websiteUrlResolver;

    /**
     * MolliePaymentLinkProvider constructor.
     *
     * @param UrlGeneratorInterface $urlGenerator
     * @param WebsiteUrlResolver $websiteUrlResolver
     * @param OroPaymentMethodUtility $paymentMethodUtility
     * @param PaymentStatusProviderInterface $paymentStatusProvider
     */
    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        WebsiteUrlResolver $websiteUrlResolver,
        OroPaymentMethodUtility $paymentMethodUtility,
        PaymentStatusProviderInterface $paymentStatusProvider
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->websiteUrlResolver = $websiteUrlResolver;
        $this->paymentMethodUtility = $paymentMethodUtility;
        $this->paymentStatusProvider = $paymentStatusProvider;
    }

    /**
     * Checks if refund button should be displayed
     *
     * @param Order $order
     *
     * @return bool
     */
    public function displayGeneratePaymentLinkButton(Order $order)
    {
        return $this->paymentStatusProvider->getPaymentStatus($order) !== PaymentStatusProvider::FULL;
    }

    /**
     * @param Order $order
     *
     * @return MolliePaymentLink
     */
    public function generatePaymentLink(Order $order)
    {
        $paymentLink = new MolliePaymentLink();
        $paymentLink->setPaymentLink($this->getSiteSpecificPaymentLink($order));
        $paymentLink->setIsMolliePaymentOnOrder($this->paymentMethodUtility->hasMolliePaymentConfig($order, true));
        $paymentLink->setIsPaymentsApiOnly(!$this->paymentMethodUtility->isOrderValidForOrdersApi($order));
        $paymentLink->setPaymentMethods(array_map(static function (MolliePaymentConfigInterface $paymentMethodConfig) {
            return $paymentMethodConfig->getLabel();
        }, $this->getPaymentMethods($order)));
        $paymentLink->setSelectedPaymentMethods($this->getSelectedPaymentMethods($order));

        return $paymentLink;
    }

    /**
     * @param string $orderId
     * @return PaymentLinkMethod|null
     */
    public function getPaymentLinkConfig($orderId)
    {
        $paymentLinkConfig = null;

        try {
            $filter = new QueryFilter();
            /** @var PaymentLinkMethod|null $paymentLinkConfig */
            $paymentLinkConfig = RepositoryRegistry::getRepository(PaymentLinkMethod::getClassName())->selectOne(
                $filter->where('shopReference', Operators::EQUALS, (string)$orderId)
            );
        } catch (\Exception $e) {
            Logger::logError(
                'Failed to load payment link configuration.',
                'Integration',
                [
                    'ShopOrderReference' => $orderId,
                    'ExceptionMessage' => $e->getMessage(),
                    'ExceptionTrace' => $e->getTraceAsString(),
                ]
            );
        }

        return $paymentLinkConfig;
    }

    /**
     * @param Order $order
     * @param Form $form
     * @throws RepositoryNotRegisteredException
     */
    public function processForm(Order $order, Form $form)
    {
        /** @var MolliePaymentLink $molliePaymentLink */
        $molliePaymentLink = $form->get('molliePaymentLink')->getData();
        if (
            !$molliePaymentLink ||
            !$this->displayGeneratePaymentLinkButton($order) ||
            $this->paymentMethodUtility->hasMolliePaymentConfig($order, true)
        ) {
            return;
        }

        $this->processPaymentLink($molliePaymentLink, $order);
    }

    /**
     * @param MolliePaymentLink $molliePaymentLink
     * @param Order $order
     * @throws RepositoryNotRegisteredException
     */
    private function processPaymentLink(MolliePaymentLink $molliePaymentLink, Order $order)
    {
        $paymentLinkConfig = $this->getPaymentLinkConfig($order->getId());
        if (!$paymentLinkConfig) {
            $paymentLinkConfig = new PaymentLinkMethod();
        }

        $paymentLinkConfig->setShopReference($order->getId());
        $paymentLinkConfig->setApiMethod($this->getApiMethodFor($molliePaymentLink, $order));
        $paymentLinkConfig->setPaymentMethods($molliePaymentLink->getSelectedPaymentMethods());

        RepositoryRegistry::getRepository(PaymentLinkMethod::getClassName())->saveOrUpdate($paymentLinkConfig);
    }

    /**
     * @param Order $order
     * @return MolliePaymentConfigInterface[]
     */
    private function getPaymentMethods(Order $order)
    {
        $paymentMethods = [];

        $paymentMethodConfigs = $this->paymentMethodUtility->getAvailablePaymentConfigs($order);
        foreach ($paymentMethodConfigs as $paymentMethodConfig) {
            if ($paymentMethodConfig->getPaymentMethodIdentifier() === MolliePaymentConfigInterface::ADMIN_PAYMENT_LINK_ID) {
                continue;
            }

            $paymentMethods[$paymentMethodConfig->getMollieId()] = $paymentMethodConfig;
        }

        return $paymentMethods;
    }

    /**
     * @param Order $order
     * @return string[]
     */
    private function getSelectedPaymentMethods(Order $order)
    {
        $paymentLinkConfig = $this->getPaymentLinkConfig($order->getId());

        return $paymentLinkConfig ? $paymentLinkConfig->getPaymentMethods() : [];
    }

    /**
     * @param MolliePaymentLink $molliePaymentLink
     * @param Order $order
     * @return string
     */
    private function getApiMethodFor(MolliePaymentLink $molliePaymentLink, Order $order)
    {
        $paymentMethodConfigs = $this->getPaymentMethods($order);
        $selectedMethodIds = $molliePaymentLink->getSelectedPaymentMethods();
        foreach ($selectedMethodIds as $selectedMethodId) {
            if (
                array_key_exists($selectedMethodId, $paymentMethodConfigs) &&
                $paymentMethodConfigs[$selectedMethodId]->isApiMethodRestricted()
            ) {
                return PaymentMethodConfig::API_METHOD_ORDERS;
            }
        }

        // Handle select all case where payment list is empty
        if (empty($selectedMethodIds) && !$molliePaymentLink->isPaymentsApiOnly()) {
            return PaymentMethodConfig::API_METHOD_ORDERS;
        }

        return PaymentMethodConfig::API_METHOD_PAYMENT;
    }

    /**
     * @param Order $order
     *
     * @return string
     */
    private function getSiteSpecificPaymentLink(Order $order)
    {
        $paymentLinkUrl = $this->urlGenerator->generate(
            'mollie_payment_link',
            ['orderId' => $order->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $websiteUrl = $this->getCleanUrl($this->websiteUrlResolver->getWebsiteUrl($order->getWebsite()));
        if ($websiteUrl && false === strpos($paymentLinkUrl, $websiteUrl)) {
            $urlParts = parse_url($paymentLinkUrl);

            $paymentLinkUrl = $websiteUrl;
            if (!empty($urlParts['path'])) {
                $paymentLinkUrl .= $urlParts['path'];
            }

            if (!empty($urlParts['query'])) {
                $paymentLinkUrl .= '?' . $urlParts['query'];
            }
        }

        return $paymentLinkUrl;
    }

    /**
     * @param string $url
     * @return string
     */
    protected function getCleanUrl($url)
    {
        return rtrim(explode('?', $url)[0], '/');
    }
}
