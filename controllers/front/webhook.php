<?php

use BTiPay\Service\Webhook\WebhookFlowService;
use BTiPay\Webhook\BTPayJwt;

if (!defined('_PS_VERSION_')) {
    exit;
}

class BtipayWebhookModuleFrontController extends ModuleFrontController
{
    private const FILE_NAME = 'webhook';

    public Btipay $module;
    public bool $ssl = true;
    public bool $display_column_left = false;
    public bool $display_column_right = false;
    private ?WebhookFlowService $flowService = null;

    private function getFlowService(): WebhookFlowService
    {
        if ($this->flowService === null) {
            $this->flowService = $this->module->getService('btipay.webhook_flow.service');
        }
        return $this->flowService;
    }

    public function initContent(): void
    {
        $flowService = $this->getFlowService();
        $webhookService = $flowService->getWebhookService();
        $logger = $flowService->getLogger();
        $config = $flowService->getConfig();

        $logger->info(sprintf('%s - Controller called', self::FILE_NAME));

        try {
            $jwt = BTPayJwt::decode(
                Tools::file_get_contents('php://input'),
                BTPayJwt::urlsafeB64Decode($config->getCallbackKey()),
                true
            );

            $payload = $this->getPayload($jwt);
            $logger->info(sprintf('%s - Payload: %s', self::FILE_NAME, json_encode($payload)));

            $webhookService->executeWebhook($payload);
            $logger->info(sprintf('%s - Controller action ended', self::FILE_NAME));

            $this->ajaxRender($this->createJsonResponse(['success' => true], 200));
        } catch (\Throwable $e) {
            $logger->error('Failed to handle webhook', [
                'Exception message' => $e->getMessage(),
                'Exception code' => $e->getCode(),
            ]);
            $this->ajaxRender($this->createJsonResponse(['error' => $e->getMessage()], 400));
        }

        exit;
    }

    private function getPayload(\stdClass $jwt): \stdClass
    {
        if (property_exists($jwt, 'payload') && $jwt->payload instanceof \stdClass) {
            return $jwt->payload;
        }
        throw new \Exception('Cannot find jwt payload');
    }

    private function createJsonResponse(array $data, int $statusCode): string
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        return json_encode($data);
    }

    protected function displayMaintenancePage(): void
    {
        // Prevent displaying the maintenance page for webhooks
    }
}
