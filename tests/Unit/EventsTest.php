<?php

namespace Eamirgh\Digipay\Tests\Unit;

use Eamirgh\Digipay\DigipayClient;
use Eamirgh\Digipay\Events\PaymentRefunded;
use Eamirgh\Digipay\Events\PaymentReversed;
use Eamirgh\Digipay\Events\PaymentVerified;
use Eamirgh\Digipay\Events\TicketCreated;
use Eamirgh\Digipay\Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

class EventsTest extends TestCase
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

    public function test_dispatches_ticket_created_event(): void
    {
        Event::fake([TicketCreated::class]);

        Http::fake([
            'https://uat.mydigipay.info/digipay/api/tickets/business?type=11' => Http::response([
                'result' => ['status' => 0, 'message' => 'Success'],
                'ticket' => 'v2:ticket123',
                'redirectUrl' => 'https://uatweb.mydigipay.info/web-pay/tgs/v2:ticket123',
            ], 200),
        ]);

        /** @var DigipayClient $client */
        $client = $this->app->make(DigipayClient::class);
        $response = $client->createTicket([
            'amount' => 100000,
            'providerId' => 'ORDER_EVENT_1',
        ]);

        Event::assertDispatched(TicketCreated::class, function (TicketCreated $event) use ($response) {
            return $event->response->ticket === $response->ticket
                && $event->request->providerId === 'ORDER_EVENT_1';
        });
    }

    public function test_dispatches_payment_verified_event(): void
    {
        Event::fake([PaymentVerified::class]);

        Http::fake([
            'https://uat.mydigipay.info/digipay/api/purchases/verify?type=11' => Http::response([
                'result' => ['status' => 0, 'message' => 'Success'],
                'trackingCode' => '10357963991735727353649',
                'providerId' => 'ORDER_EVENT_1',
                'amount' => 100000,
            ], 200),
        ]);

        /** @var DigipayClient $client */
        $client = $this->app->make(DigipayClient::class);
        $response = $client->verify('10357963991735727353649', 'ORDER_EVENT_1');

        Event::assertDispatched(PaymentVerified::class, function (PaymentVerified $event) use ($response) {
            return $event->response->trackingCode === $response->trackingCode;
        });
    }

    public function test_dispatches_payment_reversed_event(): void
    {
        Event::fake([PaymentReversed::class]);

        Http::fake([
            'https://uat.mydigipay.info/digipay/api/reverse?type=11' => Http::response([
                'result' => ['status' => 0, 'message' => 'Success'],
                'trackingCode' => 'REV_TRK',
                'providerId' => 'REV_ORD',
                'amount' => 100000,
            ], 200),
        ]);

        /** @var DigipayClient $client */
        $client = $this->app->make(DigipayClient::class);
        $response = $client->reverse('REV_TRK', 'REV_ORD');

        Event::assertDispatched(PaymentReversed::class, function (PaymentReversed $event) use ($response) {
            return $event->response->trackingCode === $response->trackingCode;
        });
    }

    public function test_dispatches_payment_refunded_event(): void
    {
        Event::fake([PaymentRefunded::class]);

        Http::fake([
            'https://uat.mydigipay.info/digipay/api/refunds?type=11' => Http::response([
                'result' => ['status' => 0, 'message' => 'Success'],
                'trackingCode' => 'REF_TRK',
            ], 200),
        ]);

        /** @var DigipayClient $client */
        $client = $this->app->make(DigipayClient::class);
        $response = $client->refund('SALE_1', 100000, 'REF_ORD');

        Event::assertDispatched(PaymentRefunded::class, function (PaymentRefunded $event) use ($response) {
            return $event->response->trackingCode === $response->trackingCode;
        });
    }
}
