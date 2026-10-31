<?php

namespace Eamirgh\Digipay\DTO;

use Illuminate\Contracts\Support\Arrayable;

class RefundResponse implements Arrayable
{
    public function __construct(
        public readonly array $result,
        public readonly string $trackingCode,
        public readonly ?string $providerId = null,
        public readonly ?int $amount = null,
        public readonly array $raw = []
    ) {
    }

    public static function fromArray(array $data, ?string $providerId = null, ?int $amount = null): self
    {
        return new self(
            result: (array) ($data['result'] ?? []),
            trackingCode: (string) ($data['trackingCode'] ?? $data['tracking_code'] ?? ''),
            providerId: $providerId ?? ($data['providerId'] ?? $data['provider_id'] ?? null),
            amount: $amount ?? (isset($data['amount']) ? (int) $data['amount'] : null),
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
            'trackingCode' => $this->trackingCode,
            'providerId' => $this->providerId,
            'amount' => $this->amount,
        ];
    }
}
