<?php

namespace Eamirgh\Digipay\DTO;

use Illuminate\Contracts\Support\Arrayable;

class DeliverResponse implements Arrayable
{
    public function __construct(
        public readonly array $result,
        public readonly array $raw = []
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            result: (array) ($data['result'] ?? []),
            raw: $data
        );
    }

    public function isSuccessful(): bool
    {
        $status = $this->result['status'] ?? null;
        return $status === 0 || $status === '0';
    }

    public function toArray(): array
    {
        return [
            'result' => $this->result,
        ];
    }
}
