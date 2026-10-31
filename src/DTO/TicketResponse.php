<?php

namespace Eamirgh\Digipay\DTO;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\RedirectResponse;

class TicketResponse implements Arrayable
{
    public function __construct(
        public readonly string $ticket,
        public readonly string $redirectUrl,
        public readonly array $result = [],
        public readonly array $insurancePolicies = [],
        public readonly ?string $providerId = null,
        public readonly ?int $amount = null,
        public readonly array $raw = []
    ) {
    }

    public static function fromArray(array $data, ?string $providerId = null, ?int $amount = null): self
    {
        return new self(
            ticket: (string) ($data['ticket'] ?? ''),
            redirectUrl: (string) ($data['redirectUrl'] ?? $data['redirect_url'] ?? ''),
            result: (array) ($data['result'] ?? []),
            insurancePolicies: (array) ($data['insurancePolicies'] ?? []),
            providerId: $providerId,
            amount: $amount,
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

    public function redirect(): RedirectResponse
    {
        return redirect()->away($this->redirectUrl);
    }

    public function toArray(): array
    {
        return [
            'ticket' => $this->ticket,
            'redirectUrl' => $this->redirectUrl,
            'providerId' => $this->providerId,
            'amount' => $this->amount,
            'result' => $this->result,
            'insurancePolicies' => $this->insurancePolicies,
        ];
    }
}
