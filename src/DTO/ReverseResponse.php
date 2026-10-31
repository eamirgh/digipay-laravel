<?php

namespace Eamirgh\Digipay\DTO;

use Illuminate\Contracts\Support\Arrayable;

class ReverseResponse implements Arrayable
{
    public function __construct(
        public readonly array $result,
        public readonly string $providerId,
        public readonly string $trackingCode,
        public readonly int $amount,
        public readonly ?string $rrn = null,
        public readonly ?string $maskedPan = null,
        public readonly ?int $paymentGateway = null,
        public readonly array $raw = []
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            result: (array) ($data['result'] ?? []),
            providerId: (string) ($data['providerId'] ?? $data['provider_id'] ?? ''),
            trackingCode: (string) ($data['trackingCode'] ?? $data['tracking_code'] ?? ''),
            amount: (int) ($data['amount'] ?? 0),
            rrn: isset($data['rrn']) ? (string) $data['rrn'] : null,
            maskedPan: isset($data['maskedPan']) ? (string) $data['maskedPan'] : null,
            paymentGateway: isset($data['paymentGateway']) ? (int) $data['paymentGateway'] : null,
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
            'providerId' => $this->providerId,
            'trackingCode' => $this->trackingCode,
            'amount' => $this->amount,
            'rrn' => $this->rrn,
            'maskedPan' => $this->maskedPan,
            'paymentGateway' => $this->paymentGateway,
        ];
    }
}
