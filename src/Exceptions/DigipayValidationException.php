<?php

namespace Eamirgh\Digipay\Exceptions;

class DigipayValidationException extends DigipayException
{
    public function __construct(
        string $message,
        int $code = 422,
        public readonly array $errors = []
    ) {
        parent::__construct($message, $code);
    }
}
