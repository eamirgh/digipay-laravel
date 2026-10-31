<?php

namespace Eamirgh\Digipay\Contracts;

use Eamirgh\Digipay\Enums\TicketType;

interface Payable
{
    /**
     * Get the payment amount in Iranian Rials.
     */
    public function getDigipayAmount(): int;

    /**
     * Get unique provider / merchant order ID.
     */
    public function getDigipayProviderId(): string;

    /**
     * Get the customer cell phone number.
     */
    public function getDigipayCellNumber(): ?string;

    /**
     * Get the custom callback URL (or null to use default).
     */
    public function getDigipayCallbackUrl(): ?string;

    /**
     * Get the preferred gateway ticket type.
     */
    public function getDigipayType(): TicketType|int|null;

    /**
     * Get additional gateway parameters (e.g. preferredGateway).
     */
    public function getDigipayAdditionalInfo(): array;

    /**
     * Get the shopping basket breakdown details.
     */
    public function getDigipayBasketDetails(): ?array;

    /**
     * Get the multi-party split payment details.
     */
    public function getDigipaySplitDetails(): ?array;
}
