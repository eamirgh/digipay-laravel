<?php

namespace Eamirgh\Digipay\DTO;

use Illuminate\Contracts\Support\Arrayable;

class VerifyResponse implements Arrayable
{
    public function __construct(
        public readonly array $result,
        public readonly string $trackingCode,
        public readonly string $providerId,
        public readonly int $amount,
        public readonly ?int $paymentGateway = null,
        public readonly ?string $terminalId = null,
        public readonly ?string $rrn = null,
        public readonly ?string $maskedPan = null,
        public readonly ?string $pspCode = null,
        public readonly ?string $pspName = null,
        public readonly ?string $fpCode = null,
        public readonly ?string $fpName = null,
        public readonly array $additionalInfo = [],
        public readonly array $raw = []
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            result: (array) ($data['result'] ?? []),
            trackingCode: (string) ($data['trackingCode'] ?? $data['tracking_code'] ?? ''),
            providerId: (string) ($data['providerId'] ?? $data['provider_id'] ?? ''),
            amount: (int) ($data['amount'] ?? 0),
            paymentGateway: isset($data['paymentGateway']) ? (int) $data['paymentGateway'] : null,
            terminalId: isset($data['terminalId']) ? (string) $data['terminalId'] : null,
            rrn: isset($data['rrn']) ? (string) $data['rrn'] : null,
            maskedPan: isset($data['maskedPan']) ? (string) $data['maskedPan'] : null,
            pspCode: isset($data['pspCode']) ? (string) $data['pspCode'] : null,
            pspName: isset($data['pspName']) ? (string) $data['pspName'] : null,
            fpCode: isset($data['fpCode']) ? (string) $data['fpCode'] : null,
            fpName: isset($data['fpName']) ? (string) $data['fpName'] : null,
            additionalInfo: (array) ($data['additionalInfo'] ?? []),
            raw: $data
        );
    }

    public function isSuccessful(): bool
    {
        $status = $this->result['status'] ?? null;
        return $status === 0 || $status === '0';
    }

    public function getStatus(): ?int
    {
        return isset($this->result['status']) ? (int) $this->result['status'] : null;
    }

    public function getMessage(): ?string
    {
        return $this->result['message'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'result' => $this->result,
            'trackingCode' => $this->trackingCode,
            'providerId' => $this->providerId,
            'amount' => $this->amount,
            'paymentGateway' => $this->paymentGateway,
            'terminalId' => $this->terminalId,
            'rrn' => $this->rrn,
            'maskedPan' => $this->maskedPan,
            'pspCode' => $this->pspCode,
            'pspName' => $this->pspName,
            'fpCode' => $this->fpCode,
            'fpName' => $this->fpName,
            'additionalInfo' => $this->additionalInfo,
        ];
    }
}
