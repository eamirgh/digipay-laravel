<?php

namespace Eamirgh\Digipay\Exceptions;

class DigipayApiException extends DigipayException
{
    public function __construct(
        string $message,
        int $code = 400,
        public readonly int|string|null $digipayStatus = null,
        public readonly ?string $trackingCode = null,
        public readonly array $responseBody = []
    ) {
        parent::__construct($message, $code);
    }
}
