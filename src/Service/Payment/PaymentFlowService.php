<?php

namespace BTiPay\Service\Payment;

use BTiPay\Config\BTiPayConfig;
use BTiPay\Command\CommandInterface;
use BTiPay\Facade\Context;
use BTiPay\Validator\Availability\AvailabilityValidatorPool;
use Psr\Log\LoggerInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PaymentFlowService
{
    public function __construct(
        private readonly BTiPayConfig $config,
        private readonly Context $context,
        private readonly LoggerInterface $logger,
        private readonly CommandInterface $orderCommand,
        private readonly CommandInterface $authorizeCommand,
        private readonly AvailabilityValidatorPool $availabilityValidator
    ) {
    }

    public function getConfig(): BTiPayConfig
    {
        return $this->config;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getOrderCommand(): CommandInterface
    {
        if ($this->config->getPhase() == BTiPayConfig::ONE_PHASE) {
            return $this->orderCommand;
        }
        return $this->authorizeCommand;
    }

    public function getAvailabilityValidator(): AvailabilityValidatorPool
    {
        return $this->availabilityValidator;
    }
}
