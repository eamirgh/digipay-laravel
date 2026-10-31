<?php

namespace Eamirgh\Digipay\Events;

use Eamirgh\Digipay\Contracts\Payable;
use Eamirgh\Digipay\DTO\VerifyResponse;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentVerified
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly VerifyResponse $response,
        public readonly ?Payable $payable = null
    ) {
    }
}
