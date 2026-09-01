<?php

namespace Mollie\Bundle\PaymentBundle\Manager;

use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\DTO\Payments\Capture;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\Http\Proxy;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\Exceptions\ReferenceNotFoundException;
use Mollie\Bundle\PaymentBundle\IntegrationCore\BusinessLogic\OrderReference\OrderReferenceService;
use Mollie\Bundle\PaymentBundle\IntegrationCore\Infrastructure\ServiceRegister;

class MollieCaptureService
{
    /**
     * @param string $shopReference
     * @param Capture|null $capture
     *
     * @return Capture
     *
     * @throws ReferenceNotFoundException
     */
    public function capturePayment($shopReference, Capture $capture = null)
    {
        $mollieReference = $this->resolveMollieReference($shopReference);

        /** @var Proxy $proxy */
        $proxy = ServiceRegister::getService(Proxy::CLASS_NAME);

        return $proxy->createCapture($capture ?: new Capture(), $mollieReference);
    }

    /**
     * Returns current capture totals for the given shop order.
     *
     * @param string $shopReference
     *
     * @return array{status: string, authorized: float, captured: float, remaining: float, currency: string}
     *
     * @throws ReferenceNotFoundException
     */
    public function getCaptureSummary($shopReference)
    {
        $mollieReference = $this->resolveMollieReference($shopReference);

        /** @var Proxy $proxy */
        $proxy = ServiceRegister::getService(Proxy::CLASS_NAME);

        $payment = $proxy->getPayment($mollieReference);
        $captures = $proxy->getCaptures($mollieReference);

        $captured = 0.0;
        foreach ($captures as $existingCapture) {
            $amount = $existingCapture->getAmount();
            if ($amount) {
                $captured += (float)$amount->getValueAmount();
            }
        }

        $authorized = $payment->getAmount() ? (float)$payment->getAmount()->getAmountValue() : 0.0;
        $remaining = max(0.0, $authorized - $captured);

        return [
            'status' => $payment->getStatus(),
            'authorized' => $authorized,
            'captured' => $captured,
            'remaining' => $remaining,
            'currency' => $payment->getAmount() ? $payment->getAmount()->getCurrency() : '',
        ];
    }

    /**
     * @param int|string $shopReference Shop order entity id (Order::getId()), not the order
     *                                  number - see OrderReferenceService::getByShopReference()
     *
     * @return string Mollie payment reference
     *
     * @throws ReferenceNotFoundException
     */
    private function resolveMollieReference($shopReference)
    {
        /** @var OrderReferenceService $orderReferenceService */
        $orderReferenceService = ServiceRegister::getService(OrderReferenceService::CLASS_NAME);
        $orderReference = $orderReferenceService->getByShopReference($shopReference);

        if (!$orderReference || !$orderReference->getMollieReference()) {
            throw new ReferenceNotFoundException("An error during payment capture occurred: order reference not found. Shop reference: {$shopReference}");
        }

        return $orderReference->getMollieReference();
    }
}
