<?php

namespace Eamirgh\Digipay\Tests\Unit;

use Eamirgh\Digipay\Contracts\Payable;
use Eamirgh\Digipay\DTO\TicketResponse;
use Eamirgh\Digipay\DTO\VerifyResponse;
use Eamirgh\Digipay\DigipayClient;
use Eamirgh\Digipay\Enums\TicketType;
use Eamirgh\Digipay\Facades\Digipay;
use Eamirgh\Digipay\Tests\TestCase;
use Eamirgh\Digipay\Traits\InteractsWithDigipay;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class FakeOrder extends Model implements Payable
{
    use InteractsWithDigipay;

    protected $guarded = [];

    public function getDigipayAmount(): int
    {
        return (int) $this->total;
    }

    public function getDigipayCellNumber(): ?string
    {
        return $this->customer_phone;
    }
}

class PayableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'https://uat.mydigipay.info/digipay/api/oauth/token' => Http::response([
                'access_token' => 'mock_token',
                'token_type' => 'bearer',
                'expires_in' => 3600,
            ], 200),
        ]);
    }

    public function test_payable_creates_ticket_via_digipay_client(): void
    {
        Http::fake([
            'https://uat.mydigipay.info/digipay/api/tickets/business?type=11' => Http::response([
                'result' => ['status' => 0, 'message' => 'Success'],
                'ticket' => 'v2:payable_ticket_1',
                'redirectUrl' => 'https://uatweb.mydigipay.info/web-pay/tgs/v2:payable_ticket_1',
            ], 200),
        ]);

        $order = new FakeOrder([
            'id' => 456,
            'total' => 250000,
            'customer_phone' => '09129998877',
        ]);

        /** @var DigipayClient $client */
        $client = $this->app->make(DigipayClient::class);

        $response = $client->pay($order);

        $this->assertInstanceOf(TicketResponse::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertSame('v2:payable_ticket_1', $response->ticket);
        $this->assertSame('456', $response->providerId);
        $this->assertSame(250000, $response->amount);

        Http::assertSent(function ($req) {
            return str_contains($req->url(), '/tickets/business?type=11')
                && $req['amount'] === 250000
                && $req['providerId'] === '456'
                && $req['cellNumber'] === '09129998877';
        });
    }

    public function test_payable_creates_ticket_directly_via_model_trait_method(): void
    {
        Http::fake([
            'https://uat.mydigipay.info/digipay/api/tickets/business?type=5' => Http::response([
                'result' => ['status' => 0, 'message' => 'Success'],
                'ticket' => 'v2:credit_ticket',
                'redirectUrl' => 'https://uatweb.mydigipay.info/web-pay/tgs/v2:credit_ticket',
            ], 200),
        ]);

        $order = new FakeOrder([
            'id' => 789,
            'total' => 1000000,
        ]);

        $response = $order->createDigipayTicket(TicketType::CREDIT);

        $this->assertInstanceOf(TicketResponse::class, $response);
        $this->assertSame('v2:credit_ticket', $response->ticket);
        $this->assertSame('789', $response->providerId);

        Http::assertSent(function ($req) {
            return str_contains($req->url(), '/tickets/business?type=5')
                && $req['amount'] === 1000000
                && $req['providerId'] === '789';
        });
    }

    public function test_payable_verification_via_client_and_model_trait(): void
    {
        Http::fake([
            'https://uat.mydigipay.info/digipay/api/purchases/verify?type=11' => Http::response([
                'result' => ['status' => 0, 'message' => 'Success'],
                'trackingCode' => 'TRK_PAYABLE_99',
                'providerId' => '456',
                'amount' => 250000,
                'rrn' => '11223344',
                'maskedPan' => '502229******1234',
            ], 200),
        ]);

        $order = new FakeOrder([
            'id' => 456,
            'total' => 250000,
        ]);

        // Via model method
        $response = $order->verifyDigipayPayment('TRK_PAYABLE_99');

        $this->assertInstanceOf(VerifyResponse::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertSame('TRK_PAYABLE_99', $response->trackingCode);
        $this->assertSame('456', $response->providerId);
        $this->assertSame('11223344', $response->rrn);

        // Via facade
        $facadeResponse = Digipay::verifyPayable($order, 'TRK_PAYABLE_99');
        $this->assertSame('TRK_PAYABLE_99', $facadeResponse->trackingCode);
    }

    public function test_payable_attaches_to_events(): void
    {
        \Illuminate\Support\Facades\Event::fake([
            \Eamirgh\Digipay\Events\TicketCreated::class,
            \Eamirgh\Digipay\Events\PaymentVerified::class,
        ]);

        Http::fake([
            'https://uat.mydigipay.info/digipay/api/tickets/business?type=11' => Http::response([
                'result' => ['status' => 0, 'message' => 'Success'],
                'ticket' => 'v2:event_ticket',
                'redirectUrl' => 'https://uatweb.mydigipay.info/web-pay/tgs/v2:event_ticket',
            ], 200),
            'https://uat.mydigipay.info/digipay/api/purchases/verify?type=11' => Http::response([
                'result' => ['status' => 0, 'message' => 'Success'],
                'trackingCode' => 'TRK_EVENT_1',
                'providerId' => '999',
                'amount' => 50000,
            ], 200),
        ]);

        $order = new FakeOrder([
            'id' => 999,
            'total' => 50000,
        ]);

        $order->createDigipayTicket();

        \Illuminate\Support\Facades\Event::assertDispatched(\Eamirgh\Digipay\Events\TicketCreated::class, function ($event) use ($order) {
            return $event->payable === $order;
        });

        $order->verifyDigipayPayment('TRK_EVENT_1');

        \Illuminate\Support\Facades\Event::assertDispatched(\Eamirgh\Digipay\Events\PaymentVerified::class, function ($event) use ($order) {
            return $event->payable === $order;
        });
    }
}
