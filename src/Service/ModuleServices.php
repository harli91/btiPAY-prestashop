<?php

namespace BTiPay\Service;

use BTiPay\Config\BTiPayConfig;
use BTiPay\Repository\CardRepository;
use BTiPay\Repository\PaymentRepository;
use BTiPay\Repository\RefundRepository;
use BTiPay\Validator\Availability\AvailabilityValidatorPool;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ModuleServices
{
    public function __construct(
        private readonly BTiPayConfig $config,
        private readonly LoggerInterface $logger,
        private readonly CardRepository $cardRepository,
        private readonly PaymentRepository $paymentRepository,
        private readonly RefundRepository $refundRepository,
        private readonly RefundService $refundService,
        private readonly AvailabilityValidatorPool $availabilityValidator,
        private readonly ?RouterInterface $router
    ) {
    }

    public function getConfig(): BTiPayConfig
    {
        return $this->config;
    }
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
    public function getCardRepository(): CardRepository
    {
        return $this->cardRepository;
    }
    public function getPaymentRepository(): PaymentRepository
    {
        return $this->paymentRepository;
    }
    public function getRefundRepository(): RefundRepository
    {
        return $this->refundRepository;
    }
    public function getRefundService(): RefundService
    {
        return $this->refundService;
    }
    public function getAvailabilityValidator(): AvailabilityValidatorPool
    {
        return $this->availabilityValidator;
    }
    public function getRouter(): ?RouterInterface
    {
        return $this->router;
    }
}
