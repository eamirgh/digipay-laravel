<?php

namespace Eamirgh\Digipay\DTO;

use Eamirgh\Digipay\Enums\TicketType;
use Illuminate\Contracts\Support\Arrayable;

class DeliverRequest implements Arrayable
{
    public function __construct(
        public string $trackingCode,
        public int $deliveryDate,
        public string $invoiceNumber,
        public array $products = [],
        public TicketType|int|null $type = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            trackingCode: (string) ($data['trackingCode'] ?? $data['tracking_code'] ?? ''),
            deliveryDate: (int) ($data['deliveryDate'] ?? $data['delivery_date'] ?? 0),
            invoiceNumber: (string) ($data['invoiceNumber'] ?? $data['invoice_number'] ?? ''),
            products: (array) ($data['products'] ?? []),
            type: isset($data['type']) ? (is_int($data['type']) ? TicketType::tryFrom($data['type']) ?? $data['type'] : $data['type']) : null
        );
    }

    public function toArray(): array
    {
        return [
            'trackingCode' => $this->trackingCode,
            'deliveryDate' => $this->deliveryDate,
            'invoiceNumber' => $this->invoiceNumber,
            'products' => $this->products,
        ];
    }
}
