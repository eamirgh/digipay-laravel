<?php

namespace Eamirgh\Digipay\Events;

use Eamirgh\Digipay\DTO\RefundResponse;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentRefunded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly RefundResponse $response
    ) {
    }
}
