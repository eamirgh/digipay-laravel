<?php

namespace Eamirgh\Digipay\DTO;

use Eamirgh\Digipay\Contracts\Payable;
use Eamirgh\Digipay\Enums\TicketType;
use Illuminate\Contracts\Support\Arrayable;

class TicketRequest implements Arrayable
{
    public function __construct(
        public int $amount,
        public string $providerId,
        public ?string $cellNumber = null,
        public ?string $callbackUrl = null,
        public array $additionalInfo = [],
        public ?array $basketDetailsDto = null,
        public ?array $splitDetailsList = null,
        public TicketType|int|null $type = null
    ) {
    }

    public static function fromPayable(Payable $payable, ?string $defaultCallbackUrl = null): self
    {
        return new self(
            amount: $payable->getDigipayAmount(),
            providerId: $payable->getDigipayProviderId(),
            cellNumber: $payable->getDigipayCellNumber(),
            callbackUrl: $payable->getDigipayCallbackUrl() ?: $defaultCallbackUrl,
            additionalInfo: $payable->getDigipayAdditionalInfo(),
            basketDetailsDto: $payable->getDigipayBasketDetails(),
            splitDetailsList: $payable->getDigipaySplitDetails(),
            type: $payable->getDigipayType()
        );
    }

    public static function fromArray(array $data, ?string $defaultCallbackUrl = null): self
    {
        return new self(
            amount: (int) ($data['amount'] ?? 0),
            providerId: (string) ($data['providerId'] ?? $data['provider_id'] ?? ''),
            cellNumber: isset($data['cellNumber']) || isset($data['cell_number']) ? (string) ($data['cellNumber'] ?? $data['cell_number']) : null,
            callbackUrl: (string) ($data['callbackUrl'] ?? $data['callback_url'] ?? $defaultCallbackUrl ?? ''),
            additionalInfo: (array) ($data['additionalInfo'] ?? $data['additional_info'] ?? []),
            basketDetailsDto: $data['basketDetailsDto'] ?? $data['basket_details'] ?? null,
            splitDetailsList: $data['splitDetailsList'] ?? $data['split_details'] ?? null,
            type: isset($data['type']) ? (is_int($data['type']) ? TicketType::tryFrom($data['type']) ?? $data['type'] : $data['type']) : null
        );
    }

    public function withPreferredGateway(int $preferredGateway): static
    {
        $this->additionalInfo['preferredGateway'] = $preferredGateway;
        return $this;
    }

    public function withBasket(string $basketId, array $items): static
    {
        $this->basketDetailsDto = [
            'basketId' => $basketId,
            'items' => $items,
        ];
        return $this;
    }

    public function withSplits(array $splits): static
    {
        $this->splitDetailsList = $splits;
        return $this;
    }

    public function toArray(): array
    {
        $data = [
            'amount' => $this->amount,
            'providerId' => $this->providerId,
        ];

        if ($this->callbackUrl !== null && $this->callbackUrl !== '') {
            $data['callbackUrl'] = $this->callbackUrl;
        }

        if ($this->cellNumber !== null && $this->cellNumber !== '') {
            $data['cellNumber'] = $this->cellNumber;
        }

        if (!empty($this->additionalInfo)) {
            $data['additionalInfo'] = $this->additionalInfo;
        }

        if ($this->basketDetailsDto !== null) {
            $data['basketDetailsDto'] = $this->basketDetailsDto;
        }

        if ($this->splitDetailsList !== null) {
            $data['splitDetailsList'] = $this->splitDetailsList;
        }

        return $data;
    }
}
