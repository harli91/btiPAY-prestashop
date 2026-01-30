<?php

namespace BTiPay\Service\Webhook;

use BTiPay\Config\BTiPayConfig;
use BTiPay\Webhook\WebhookService;
use Psr\Log\LoggerInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class WebhookFlowService
{
    public function __construct(
        private readonly WebhookService $webhookService,
        private readonly LoggerInterface $logger,
        private readonly BTiPayConfig $config
    ) {
    }

    public function getWebhookService(): WebhookService
    {
        return $this->webhookService;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getConfig(): BTiPayConfig
    {
        return $this->config;
    }
}
