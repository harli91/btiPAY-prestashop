<?php

namespace BTiPay\Controller;

if (!defined('_PS_VERSION_')) {
    exit;
}

use BTiPay\Service\CaptureService;
use BTiPay\Service\RefundService;
use BTiPay\Service\CancelService;
use PrestaShopBundle\Controller\Admin\PrestaShopAdminController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiPaymentController extends PrestaShopAdminController
{
    public function __construct(
        private readonly CaptureService $captureService,
        private readonly RefundService $refundService,
        private readonly CancelService $cancelService
    ) {
    }

    public function handleRequest(Request $request, $action, $orderId): JsonResponse
    {
        $amount = $request->request->get('amount');

        if (!is_numeric($orderId)) {
            return $this->jsonError('Invalid value for `orderId`');
        }

        $data['order'] = new \Order($orderId);
        $type = 'btipay_api_payment_handle';

        return match ($action) {
            'capture' => $this->handleCapture($data, $type, $amount),
            'refund' => $this->handleRefund($data, $type, $amount),
            'cancel' => $this->handleCancel($data, $type, $amount),
            default => new JsonResponse(['error' => 'Unknown action'], Response::HTTP_BAD_REQUEST),
        };
    }

    private function handleCapture(array $data, string $type, ?string $amount): JsonResponse
    {
        try {
            $this->captureService->execute($data, $type, $amount);
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    private function handleRefund(array $data, string $type, ?string $amount): JsonResponse
    {
        try {
            $result = $this->refundService->customRefund($data, $type, $amount);
            return new JsonResponse(['success' => true, 'message' => $result]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    private function handleCancel(array $data, string $type, ?string $amount): JsonResponse
    {
        try {
            $result = $this->cancelService->execute($data, $type, $amount);
            return new JsonResponse(['success' => true, 'message' => $result]);
        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    private function jsonError(string $message): JsonResponse
    {
        $this->addFlash('error', $message);
        return new JsonResponse(['error' => true, 'message' => $message], Response::HTTP_BAD_REQUEST);
    }
}
