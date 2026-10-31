<?php

namespace Eamirgh\Digipay\DTO;

use Eamirgh\Digipay\Enums\TicketType;
use Illuminate\Contracts\Support\Arrayable;

class RefundRequest implements Arrayable
{
    public function __construct(
        public string $saleTrackingCode,
        public int $amount,
        public string $providerId,
        public TicketType|int|null $type = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            saleTrackingCode: (string) ($data['saleTrackingCode'] ?? $data['sale_tracking_code'] ?? ''),
            amount: (int) ($data['amount'] ?? 0),
            providerId: (string) ($data['providerId'] ?? $data['provider_id'] ?? ''),
            type: isset($data['type']) ? (is_int($data['type']) ? TicketType::tryFrom($data['type']) ?? $data['type'] : $data['type']) : null
        );
    }

    public function toArray(): array
    {
        return [
            'saleTrackingCode' => $this->saleTrackingCode,
            'amount' => $this->amount,
            'providerId' => $this->providerId,
        ];
    }
}
