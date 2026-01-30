<?php

use BTiPay\Service\Payment\ReturnFlowService;
use BTransilvania\Api\Model\Response\GetOrderStatusResponseModel;

if (!defined('_PS_VERSION_')) {
    exit;
}

class BtipayReturnModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    private ?ReturnFlowService $flowService = null;

    private function getFlowService(): ReturnFlowService
    {
        if ($this->flowService === null) {
            $this->flowService = $this->module->getService('btipay.return_flow.service');
        }
        return $this->flowService;
    }

    public function postProcess(): void
    {
        if (!$this->module->active) {
            exit;
        }

        $orderId = null;
        $secureKey = null;

        try {
            $this->validateReturn();

            $flowService = $this->getFlowService();
            $ipayId = Tools::getValue('orderId');
            $token = Tools::getValue('token');
            $saveCard = Tools::getValue('save_card');
            $secureKey = Tools::getValue('secureKey');

            $paymentsRepository = $flowService->getPaymentRepository();
            $order = $paymentsRepository->getOrderByIPayId($ipayId);

            if ($order) {
                $this->validateOrderOwnership($order, $secureKey);
            }

            /** @var GetOrderStatusResponseModel $response */
            $response = $flowService->getPaymentDetailsCommand()->execute([
                'ipayId' => $ipayId,
                'token' => $token,
                'context' => $flowService->getContext(),
                'saveCard' => $saveCard,
            ]);

            $orderId = explode('-', $response->orderNumber)[0];

            if ($orderId !== $order->id) {
                $order = new Order($orderId);
                $this->validateOrderOwnership($order, $secureKey);
            }

            if ($response->isSuccess() && $response->paymentIsAccepted()) {
                $this->handleSuccess($order);
            } else {
                $this->handleError($response->getCustomerError() ?: 'Payment failed!', $orderId, $secureKey);
            }
        } catch (\Exception $e) {
            $this->handleError($e->getMessage(), $orderId, $secureKey);
        }
    }

    protected function validateReturn(): void
    {
        if (!is_string(Tools::getValue('orderId'))) {
            throw new \Exception('Invalid return `orderId`', 1);
        }

        if (!is_string(Tools::getValue('token'))) {
            throw new \Exception('Invalid return `token`', 1);
        }
    }

    private function handleSuccess(Order $order): void
    {
        Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $order->id_cart . '&id_module=' . $this->module->id . '&id_order=' . $order->id . '&key=' . $order->secure_key);
    }

    private function handleError(string $errorMessage, ?int $orderId, ?string $secureKey = null): void
    {
        if (empty($errorMessage)) {
            $errorMessage = $this->translate('Your payment was unsuccessful. Please try again or choose another payment method.');
        }

        $this->context->smarty->assign([
            'order_id' => $orderId,
            'errors' => $errorMessage,
            'payment_link' => $this->context->link->getModuleLink(
                $this->module->name,
                'payment',
                ['orderId' => $orderId, 'secureKey' => $secureKey],
                true
            ),
        ]);

        $this->setTemplate('module:btipay/views/templates/front/error.tpl');
    }

    private function validateOrderOwnership(Order $order, ?string $secureKey): void
    {
        if ((int) $order->id_customer !== (int) $this->context->customer->id) {
            throw new \Exception($this->translate('Order does not belong to the authenticated user.'));
        }

        if ($order->secure_key !== $secureKey) {
            throw new \Exception($this->translate('Invalid secure key.'));
        }
    }

    private function translate(string $string): string
    {
        return $this->module->getTranslator()->trans($string, [], 'Modules.Btipay.Btipay');
    }
}
