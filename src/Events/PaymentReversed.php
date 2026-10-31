<?php

namespace Eamirgh\Digipay\Events;

use Eamirgh\Digipay\DTO\ReverseResponse;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentReversed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ReverseResponse $response
    ) {
    }
}
