<?php

namespace Eamirgh\Digipay\DTO;

use Eamirgh\Digipay\Enums\TicketType;
use Illuminate\Contracts\Support\Arrayable;

class VerifyRequest implements Arrayable
{
    public function __construct(
        public string $trackingCode,
        public string $providerId,
        public TicketType|int|null $type = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            trackingCode: (string) ($data['trackingCode'] ?? $data['tracking_code'] ?? ''),
            providerId: (string) ($data['providerId'] ?? $data['provider_id'] ?? ''),
            type: isset($data['type']) ? (is_int($data['type']) ? TicketType::tryFrom($data['type']) ?? $data['type'] : $data['type']) : null
        );
    }

    public function toArray(): array
    {
        return [
            'trackingCode' => $this->trackingCode,
            'providerId' => $this->providerId,
        ];
    }
}
