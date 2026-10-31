<?php

namespace Eamirgh\Digipay\Events;

use Eamirgh\Digipay\Contracts\Payable;
use Eamirgh\Digipay\DTO\TicketRequest;
use Eamirgh\Digipay\DTO\TicketResponse;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly TicketRequest $request,
        public readonly TicketResponse $response,
        public readonly ?Payable $payable = null
    ) {
    }
}
