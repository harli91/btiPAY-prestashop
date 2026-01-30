<?php

namespace BTiPay\Service\Payment;

use BTiPay\Command\CommandInterface;
use BTiPay\Facade\Context;
use BTiPay\Repository\PaymentRepository;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ReturnFlowService
{
    public function __construct(
        private readonly PaymentRepository $paymentRepository,
        private readonly Context $context,
        private readonly CommandInterface $paymentDetailsCommand
    ) {
    }

    public function getPaymentRepository(): PaymentRepository
    {
        return $this->paymentRepository;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getPaymentDetailsCommand(): CommandInterface
    {
        return $this->paymentDetailsCommand;
    }
}
