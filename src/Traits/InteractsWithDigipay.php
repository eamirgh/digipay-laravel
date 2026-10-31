<?php

namespace Eamirgh\Digipay\Traits;

use Eamirgh\Digipay\DTO\TicketResponse;
use Eamirgh\Digipay\DTO\VerifyResponse;
use Eamirgh\Digipay\DigipayManager;
use Eamirgh\Digipay\Enums\TicketType;

trait InteractsWithDigipay
{
    public function getDigipayProviderId(): string
    {
        if (method_exists($this, 'getKey')) {
            return (string) $this->getKey();
        }

        return (string) ($this->id ?? '');
    }

    public function getDigipayAmount(): int
    {
        return (int) ($this->amount ?? $this->total_amount ?? $this->total ?? 0);
    }

    public function getDigipayCellNumber(): ?string
    {
        return $this->cell_number ?? $this->phone ?? $this->mobile ?? null;
    }

    public function getDigipayCallbackUrl(): ?string
    {
        return null;
    }

    public function getDigipayType(): TicketType|int|null
    {
        return null;
    }

    public function getDigipayAdditionalInfo(): array
    {
        return [];
    }

    public function getDigipayBasketDetails(): ?array
    {
        return null;
    }

    public function getDigipaySplitDetails(): ?array
    {
        return null;
    }

    public function createDigipayTicket(TicketType|int|null $type = null): TicketResponse
    {
        /** @var DigipayManager $manager */
        $manager = app('digipay');
        return $manager->pay($this, $type);
    }

    public function verifyDigipayPayment(?string $trackingCode = null, TicketType|int|null $type = null): VerifyResponse
    {
        /** @var DigipayManager $manager */
        $manager = app('digipay');
        return $manager->verifyPayable($this, $trackingCode, $type);
    }
}
