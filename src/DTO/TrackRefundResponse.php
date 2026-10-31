<?php

namespace Eamirgh\Digipay\DTO;

use Illuminate\Contracts\Support\Arrayable;

class TrackRefundResponse implements Arrayable
{
    public function __construct(
        public readonly string $providerId,
        public readonly string $trackingCode,
        public readonly int $status,
        public readonly int $resultCode,
        public readonly ?string $transferDate = null,
        public readonly ?int $destinationType = null,
        public readonly ?string $destination = null,
        public readonly array $result = [],
        public readonly array $raw = []
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            providerId: (string) ($data['providerId'] ?? $data['provider_id'] ?? ''),
            trackingCode: (string) ($data['trackingCode'] ?? $data['tracking_code'] ?? ''),
            status: (int) ($data['status'] ?? 0),
            resultCode: (int) ($data['resultCode'] ?? $data['result_code'] ?? 0),
            transferDate: isset($data['transferDate']) ? (string) $data['transferDate'] : null,
            destinationType: isset($data['destinationType']) ? (int) $data['destinationType'] : null,
            destination: isset($data['destination']) ? (string) $data['destination'] : null,
            result: (array) ($data['result'] ?? []),
            raw: $data
        );
    }

    public function isSuccessful(): bool
    {
        return $this->status === 0;
    }

    public function isPending(): bool
    {
        return $this->status === 2;
    }

    public function isFailed(): bool
    {
        return $this->status === 1;
    }

    public function toArray(): array
    {
        return [
            'providerId' => $this->providerId,
            'trackingCode' => $this->trackingCode,
            'status' => $this->status,
            'resultCode' => $this->resultCode,
            'transferDate' => $this->transferDate,
            'destinationType' => $this->destinationType,
            'destination' => $this->destination,
            'result' => $this->result,
        ];
    }
}
