<?php

use BTiPay\Service\Payment\PaymentFlowService;
use BTransilvania\Api\Model\Response\RegisterResponseModel;

if (!defined('_PS_VERSION_')) {
    exit;
}

class BtipayPaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    private ?PaymentFlowService $flowService = null;

    private function getFlowService(): PaymentFlowService
    {
        if ($this->flowService === null) {
            $this->flowService = $this->module->getService('btipay.payment_flow.service');
        }
        return $this->flowService;
    }

    public function postProcess(): void
    {
        $flowService = $this->getFlowService();
        $context = $flowService->getContext();
        $btConfig = $flowService->getConfig();
        $btLogger = $flowService->getLogger();

        if (!Tools::getIsset('orderId')) {
            if (!$this->checkIfContextIsValid() || !$this->checkIfPaymentOptionIsAvailable()) {
                Tools::redirect($this->context->link->getPageLink(
                    'order',
                    true,
                    (int) $this->context->language->id,
                    ['step' => 1]
                ));
            }

            $customer = new Customer($this->context->cart->id_customer);

            if (!Validate::isLoadedObject($customer)) {
                Tools::redirect($this->context->link->getPageLink(
                    'order',
                    true,
                    (int) $this->context->language->id,
                    ['step' => 1]
                ));
            }

            $paymentStatus = $btConfig->getNewOrderStatus();
            $total = $context->getOrderTotal();
            $customer = new Customer($context->getCustomerId());
            $secureKey = $customer->secure_key;

            $this->module->validateOrder(
                $context->getCartId(),
                $paymentStatus,
                $total,
                $this->module->displayName,
                null,
                [],
                $context->getCurrencyId(),
                false,
                $secureKey
            );

            $orderId = $this->module->currentOrder;
        } else {
            $orderId = Tools::getValue('orderId');
            $secureKey = Tools::getValue('secureKey');

            if (empty($secureKey)) {
                $this->displayError([$this->translate('Missing secure key.')]);
                return;
            }

            $order = new Order($orderId);

            if (!Validate::isLoadedObject($order)) {
                $btLogger->error('Order not found.');
                $this->displayError([$this->translate('Order not found.')]);
                return;
            }

            if ($order->secure_key !== $secureKey) {
                $btLogger->error('Invalid secure key.');
                $this->displayError([$this->translate('Invalid secure key.')], $orderId);
                return;
            }
        }

        $orderCommand = $flowService->getOrderCommand();
        $errors = [];

        try {
            $useNewCard = Tools::getValue('bt_ipay_use_new_card', 'no') === 'yes';
            $saveCard = Tools::getValue('bt_ipay_save_cards', 'no') === 'save';
            $selectedCardId = Tools::getValue('bt_ipay_card_id');
            $cardOnFileEnabled = $btConfig->isCardOnFileEnabled();

            /** @var RegisterResponseModel $response */
            $response = $orderCommand->execute([
                'orderId' => $orderId,
                'useNewCard' => $useNewCard,
                'saveCard' => $saveCard,
                'selectedCardId' => $selectedCardId,
                'cardOnFileEnabled' => $cardOnFileEnabled,
                'context' => $context,
                'secureKey' => $secureKey,
            ]);

            if ($response->isError()) {
                $errors[] = $response->getErrorCode() . ': ' . $this->translate($response->getErrorMessage());
                $btLogger->error($response->getErrorCode() . ': ' . $response->getErrorMessage());
            }

            if ($response->hasRedirect()) {
                Tools::redirect($response->getRedirectUrl());
            }
        } catch (\BTiPay\Exception\CommandException $e) {
            $errors[] = $this->translate($e->getMessage());
            $btLogger->error($e->getMessage());
        } catch (\BTransilvania\Api\Exception\ApiException $e) {
            $errors[] = $this->translate($e->getPlainMessage());
            $btLogger->error($e->getMessage());
        } catch (\Exception $e) {
            $errors[] = $this->translate('An error occurred. Please contact us for more details.');
            $btLogger->error($e->getMessage());
        }

        if (!empty($errors)) {
            $this->displayError($errors, $orderId, $secureKey);
        }
    }

    private function checkIfContextIsValid(): bool
    {
        return Validate::isLoadedObject($this->context->cart)
            && Validate::isUnsignedInt($this->context->cart->id_customer)
            && Validate::isUnsignedInt($this->context->cart->id_address_delivery)
            && Validate::isUnsignedInt($this->context->cart->id_address_invoice);
    }

    private function checkIfPaymentOptionIsAvailable(): bool
    {
        $availabilityValidator = $this->getFlowService()->getAvailabilityValidator();
        if (!$availabilityValidator->validate(['cart' => $this->context->cart])) {
            return false;
        }

        $modules = Module::getPaymentModules();
        if (empty($modules)) {
            return false;
        }

        foreach ($modules as $module) {
            if (isset($module['name']) && $this->module->name === $module['name']) {
                return true;
            }
        }

        return false;
    }

    protected function displayError(array $errors = [], ?int $invoicenumber = null, ?string $secureKey = null): void
    {
        $errorMessage = empty($errors)
            ? $this->translate('Your payment was unsuccessful. Please try again or choose another payment method.')
            : implode(PHP_EOL, $errors);

        $this->context->smarty->assign([
            'order_id' => $invoicenumber,
            'errors' => $errorMessage,
            'payment_link' => $this->context->link->getModuleLink(
                $this->module->name,
                'payment',
                ['orderId' => $invoicenumber, 'secureKey' => $secureKey],
                true
            ),
        ]);

        $this->setTemplate('module:btipay/views/templates/front/error.tpl');
    }

    private function translate(string $string): string
    {
        return $this->module->getTranslator()->trans($string, [], 'Modules.Btipay.Btipay');
    }
}
