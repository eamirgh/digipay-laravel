<?php

namespace Eamirgh\Digipay\Tests\Unit;

use Eamirgh\Digipay\Enums\DestinationType;
use Eamirgh\Digipay\Enums\PaymentGateway;
use Eamirgh\Digipay\Enums\PreferredGateway;
use Eamirgh\Digipay\Enums\ProductType;
use Eamirgh\Digipay\Enums\PspCode;
use Eamirgh\Digipay\Enums\TicketType;
use Eamirgh\Digipay\Tests\TestCase;

class EnumsTest extends TestCase
{
    public function test_ticket_type_enum(): void
    {
        $this->assertSame(0, TicketType::IPG->value);
        $this->assertSame(5, TicketType::CREDIT->value);
        $this->assertSame(11, TicketType::WALLET->value);
        $this->assertSame(13, TicketType::BNPL->value);
        $this->assertSame(24, TicketType::CREDIT_CARD->value);
    }

    public function test_payment_gateway_enum(): void
    {
        $this->assertSame(0, PaymentGateway::IPG->value);
        $this->assertSame(3, PaymentGateway::WALLET->value);
        $this->assertSame(4, PaymentGateway::CPG->value);
    }

    public function test_preferred_gateway_enum(): void
    {
        $this->assertSame(0, PreferredGateway::WALLET->value);
        $this->assertSame(2, PreferredGateway::IPG->value);
    }

    public function test_product_type_enum(): void
    {
        $this->assertSame(1, ProductType::DURABLE->value);
        $this->assertSame(2, ProductType::CONSUMABLE->value);
        $this->assertSame(3, ProductType::SERVICE->value);
        $this->assertSame(4, ProductType::DURABLE_CONSUMABLE->value);
    }

    public function test_destination_type_enum(): void
    {
        $this->assertSame(0, DestinationType::MASKED_PAN->value);
        $this->assertSame(1, DestinationType::IBAN->value);
        $this->assertSame(2, DestinationType::WALLET->value);
        $this->assertSame(3, DestinationType::CREDIT->value);
    }

    public function test_psp_code_enum(): void
    {
        $this->assertSame('001', PspCode::SAMAN->value);
        $this->assertSame('002', PspCode::PARSIAN->value);
        $this->assertSame('003', PspCode::MELLAT->value);
        $this->assertSame('004', PspCode::ENOVIN->value);
        $this->assertSame('005', PspCode::PASARGAD->value);
        $this->assertSame('006', PspCode::FANAVA->value);
        $this->assertSame('007', PspCode::MELLI->value);
        $this->assertSame('008', PspCode::IRKISH->value);
    }
}
