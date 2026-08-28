<?php

namespace Mollie\Bundle\PaymentBundle\Manager;

use Mollie\Bundle\PaymentBundle\Form\Entity\MollieCapture;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Payments\Amount;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Payments\Capture;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Configuration\Configuration;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\Logger\Logger;
use Oro\Bundle\LocaleBundle\Twig\LocaleExtension;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Symfony\Component\Form\Form;
use Symfony\Contracts\Translation\TranslatorInterface;

class MollieCaptureProvider
{
    /** @var Configuration */
    private $configService;

    /** @var MollieCaptureService */
    private $captureService;

    /** @var OroPaymentMethodUtility */
    private $paymentMethodUtility;

    /** @var LocaleExtension */
    private $localeExtension;

    /** @var TranslatorInterface */
    private $translationService;

    /** @var PaymentTransactionProvider */
    private $paymentTransactionProvider;

    public function __construct(
        Configuration $configService,
        MollieCaptureService $captureService,
        OroPaymentMethodUtility $paymentMethodUtility,
        LocaleExtension $localeExtension,
        TranslatorInterface $translationService,
        PaymentTransactionProvider $paymentTransactionProvider
    ) {
        $this->configService = $configService;
        $this->captureService = $captureService;
        $this->paymentMethodUtility = $paymentMethodUtility;
        $this->localeExtension = $localeExtension;
        $this->translationService = $translationService;
        $this->paymentTransactionProvider = $paymentTransactionProvider;
    }

    /**
     * @param Order $order
     *
     * @return bool
     */
    public function displayCaptureOption($order): bool
    {
        if (!$order) {
            return false;
        }

        return $this->paymentMethodUtility->hasMolliePaymentConfig($order)
            && $this->paymentMethodUtility->isPaymentAuthorizedOnly($order);
    }

    /**
     * @param Order $order
     *
     * @return MollieCapture
     */
    public function getMollieCapture($order): MollieCapture
    {
        $capture = new MollieCapture();
        $capture->setCurrency($order->getCurrency());
        $capture->setCurrencySymbol($this->localeExtension->getCurrencySymbolByCurrency($order->getCurrency()));

        try {
            $summary = $this->configService->doWithContext(
                $this->paymentMethodUtility->getChannelId($order),
                function () use ($order) {
                    return $this->captureService->getCaptureSummary($order->getId());
                }
            );

            if (!empty($summary['currency'])) {
                $capture->setCurrency($summary['currency']);
                $capture->setCurrencySymbol($this->localeExtension->getCurrencySymbolByCurrency($summary['currency']));
            }

            $capture->setTotalAuthorized((float)$summary['authorized']);
            $capture->setTotalCaptured((float)$summary['captured']);
            $capture->setTotalValue((float)$summary['remaining']);
            $capture->setAmount((float)$summary['remaining']);
        } catch (\Throwable $exception) {
            Logger::logError(
                'Failed to fetch capture summary from Mollie',
                'Integration',
                [
                    'OrderId' => $order->getIdentifier(),
                    'ExceptionMessage' => $exception->getMessage(),
                ]
            );

            $transaction = $this->paymentTransactionProvider->getPaymentTransaction($order);
            $fallback = $transaction ? (float)$transaction->getAmount() : 0.0;
            $capture->setTotalAuthorized($fallback);
            $capture->setTotalCaptured(0.0);
            $capture->setTotalValue($fallback);
            $capture->setAmount($fallback);
        }

        return $capture;
    }

    /**
     * @param Form $form
     *
     * @return array
     */
    public function processCapture($form): array
    {
        try {
            $actionData = $form->getData();
            /** @var Order $order */
            $order = $actionData->data;
            /** @var MollieCapture|null $mollieCapture */
            $mollieCapture = $actionData->mollieCapture ?? null;

            if (!$mollieCapture) {
                return [
                    'success' => false,
                    'message' => $this->translationService->trans('mollie.payment.capture.errorMessage', [
                        '{api_message}' => $this->translationService->trans('mollie.payment.capture.amount.invalid'),
                    ]),
                ];
            }

            $orderId = $order->getId();
            $channelId = $this->paymentMethodUtility->getChannelId($order);

            return $this->configService->doWithContext($channelId, function () use ($orderId, $mollieCapture) {
                $summary = $this->captureService->getCaptureSummary($orderId);
                $requested = (float)$mollieCapture->getAmount();

                if ($requested <= 0) {
                    return [
                        'success' => false,
                        'message' => $this->translationService->trans('mollie.payment.capture.amount.invalid'),
                    ];
                }

                if ($requested - (float)$summary['remaining'] > 0.001) {
                    return [
                        'success' => false,
                        'message' => $this->translationService->trans('mollie.payment.capture.amount.exceeds_available'),
                    ];
                }

                $captureDto = new Capture();
                $amount = new Amount();
                $amount->setCurrency($mollieCapture->getCurrency() ?: $summary['currency']);
                $amount->setValue(number_format($requested, 2, '.', ''));
                $captureDto->setAmount($amount);

                $this->captureService->capturePayment($orderId, $captureDto);

                return [
                    'success' => true,
                    'message' => $this->translationService->trans('mollie.payment.capture.successMessage'),
                ];
            });
        } catch (\Throwable $exception) {
            Logger::logError(
                'Failed to process capture action',
                'Integration',
                [
                    'ExceptionMessage' => $exception->getMessage(),
                    'ExceptionTrace' => $exception->getTraceAsString(),
                ]
            );

            return [
                'success' => false,
                'message' => $this->translationService->trans(
                    'mollie.payment.capture.errorMessage',
                    ['{api_message}' => $exception->getMessage()]
                ),
            ];
        }
    }
}