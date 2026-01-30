<?php

namespace BTiPay\Service\Account;

use BTiPay\Config\BTiPayConfig;
use BTiPay\Facade\Context;
use BTiPay\Repository\CardRepository;
use BTiPay\Service\CardService;
use BTiPay\Service\PaymentDetailsService;

if (!defined('_PS_VERSION_')) {
    exit;
}

class AccountFlowService
{
    public function __construct(
        private readonly BTiPayConfig $config,
        private readonly CardRepository $cardRepository,
        private readonly Context $context,
        private readonly CardService $cardService,
        private readonly PaymentDetailsService $paymentDetailsService
    ) {
    }

    public function getConfig(): BTiPayConfig
    {
        return $this->config;
    }

    public function getCardRepository(): CardRepository
    {
        return $this->cardRepository;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getCardService(): CardService
    {
        return $this->cardService;
    }

    public function getPaymentDetailsService(): PaymentDetailsService
    {
        return $this->paymentDetailsService;
    }
}
