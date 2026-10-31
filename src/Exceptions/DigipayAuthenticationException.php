<?php

namespace Eamirgh\Digipay\Exceptions;

class DigipayAuthenticationException extends DigipayException
{
    public function __construct(
        string $message = 'Authentication with Digipay failed.',
        int $code = 401,
        public readonly array $responseBody = []
    ) {
        parent::__construct($message, $code);
    }
}
