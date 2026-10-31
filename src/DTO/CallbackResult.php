<?php

namespace Eamirgh\Digipay\DTO;

use Illuminate\Contracts\Support\Arrayable;

class CallbackResult implements Arrayable
{
    public function __construct(
        public readonly int $amount,
        public readonly string $providerId,
        public readonly ?string $trackingCode = null,
        public readonly ?string $rrn = null,
        public readonly array $psp = [],
        public readonly bool $isCredit = false,
        public readonly string $result = 'FAILURE',
        public readonly ?int $type = null,
        public readonly array $raw = []
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            amount: (int) ($data['amount'] ?? 0),
            providerId: (string) ($data['providerId'] ?? $data['provider_id'] ?? ''),
            trackingCode: isset($data['trackingCode']) || isset($data['tracking_code']) ? (string) ($data['trackingCode'] ?? $data['tracking_code']) : null,
            rrn: isset($data['rrn']) ? (string) $data['rrn'] : null,
            psp: (array) ($data['psp'] ?? []),
            isCredit: (bool) ($data['isCredit'] ?? $data['is_credit'] ?? false),
            result: strtoupper((string) ($data['result'] ?? 'FAILURE')),
            type: isset($data['type']) ? (int) $data['type'] : null,
            raw: $data
        );
    }

    public function isSuccessful(): bool
    {
        return $this->result === 'SUCCESS';
    }

    public function getPspCode(): ?string
    {
        return $this->psp['code'] ?? null;
    }

    public function getPspName(): ?string
    {
        return $this->psp['name'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'providerId' => $this->providerId,
            'trackingCode' => $this->trackingCode,
            'rrn' => $this->rrn,
            'psp' => $this->psp,
            'isCredit' => $this->isCredit,
            'result' => $this->result,
            'type' => $this->type,
        ];
    }
}
