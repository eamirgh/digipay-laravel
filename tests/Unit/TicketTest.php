<?php

namespace Eamirgh\Digipay\Tests\Unit;

use Eamirgh\Digipay\DigipayClient;
use Eamirgh\Digipay\DTO\TicketRequest;
use Eamirgh\Digipay\DTO\TicketResponse;
use Eamirgh\Digipay\Enums\TicketType;
use Eamirgh\Digipay\Exceptions\DigipayApiException;
use Eamirgh\Digipay\Exceptions\DigipayValidationException;
use Eamirgh\Digipay\Tests\TestCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;

class TicketTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'https://uat.mydigipay.info/digipay/api/oauth/token' => Http::response([
                'access_token' => 'mock_access_token_xyz',
                'token_type' => 'bearer',
                'expires_in' => 3600,
            ], 200),
        ]);
    }

    public function test_create_ticket_with_ticket_request_dto(): void
    {
        Http::fake([
            'https://uat.mydigipay.info/digipay/api/tickets/business?type=11' => Http::response([
                'result' => [
                    'title' => 'SUCCESS',
                    'status' => 0,
                    'message' => 'عملیات با موفقیت انجام شد',
                    'level' => 'INFO',
                ],
                'ticket' => 'v2:ab17ec383d654be3b009f9fc45202f80',
                'redirectUrl' => 'https://uatweb.mydigipay.info/web-pay/tgs/v2:ab17ec383d654be3b009f9fc45202f80',
                'insurancePolicies' => [],
            ], 200),
        ]);

        /** @var DigipayClient $client */
        $client = $this->app->make(DigipayClient::class);

        $request = (new TicketRequest(
            amount: 100000,
            providerId: 'ORDER_123',
            cellNumber: '09120000000',
            callbackUrl: 'https://example.com/callback'
        ))->withPreferredGateway(2);

        $response = $client->createTicket($request);

        $this->assertInstanceOf(TicketResponse::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertSame('v2:ab17ec383d654be3b009f9fc45202f80', $response->ticket);
        $this->assertSame('https://uatweb.mydigipay.info/web-pay/tgs/v2:ab17ec383d654be3b009f9fc45202f80', $response->redirectUrl);
        $this->assertSame('ORDER_123', $response->providerId);
        $this->assertSame(100000, $response->amount);

        $redirect = $response->redirect();
        $this->assertInstanceOf(RedirectResponse::class, $redirect);
        $this->assertSame('https://uatweb.mydigipay.info/web-pay/tgs/v2:ab17ec383d654be3b009f9fc45202f80', $redirect->getTargetUrl());

        Http::assertSent(function ($req) {
            return str_contains($req->url(), '/tickets/business?type=11')
                && $req->header('Authorization')[0] === 'Bearer mock_access_token_xyz'
                && $req->header('Agent')[0] === 'WEB'
                && $req->header('Digipay-Version')[0] === '2022-02-02'
                && $req['amount'] === 100000
                && $req['providerId'] === 'ORDER_123'
                && $req['cellNumber'] === '09120000000'
                && $req['additionalInfo']['preferredGateway'] === 2;
        });
    }

    public function test_create_ticket_with_array_and_fallback_callback_url(): void
    {
        Http::fake([
            'https://uat.mydigipay.info/digipay/api/tickets/business?type=5' => Http::response([
                'result' => [
                    'title' => 'SUCCESS',
                    'status' => 0,
                    'message' => 'Success',
                ],
                'ticket' => 'ticket_credit_123',
                'redirectUrl' => 'https://uatweb.mydigipay.info/web-pay/tgs/ticket_credit_123',
            ], 200),
        ]);

        /** @var DigipayClient $client */
        $client = $this->app->make(DigipayClient::class);

        $response = $client->createTicket([
            'amount' => 50000,
            'providerId' => 'CREDIT_ORD_1',
        ], type: TicketType::CREDIT);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('ticket_credit_123', $response->ticket);

        Http::assertSent(function ($req) {
            return str_contains($req->url(), '/tickets/business?type=5')
                && $req['callbackUrl'] === 'https://example.com/callback'
                && $req['amount'] === 50000;
        });
    }

    public function test_create_ticket_fails_validation_when_amount_missing(): void
    {
        /** @var DigipayClient $client */
        $client = $this->app->make(DigipayClient::class);

        $this->expectException(DigipayValidationException::class);
        $client->createTicket([
            'providerId' => 'NO_AMOUNT',
        ]);
    }

    public function test_create_ticket_handles_digipay_api_error(): void
    {
        Http::fake([
            'https://uat.mydigipay.info/digipay/api/tickets/business?type=11' => Http::response([
                'result' => [
                    'title' => 'ERROR',
                    'status' => 1054,
                    'message' => 'اطلاعات ورودی نامعتبر است',
                    'level' => 'ERROR',
                ],
            ], 422),
        ]);

        /** @var DigipayClient $client */
        $client = $this->app->make(DigipayClient::class);

        $this->expectException(DigipayApiException::class);
        $this->expectExceptionCode(422);

        $client->createTicket([
            'amount' => 10000,
            'providerId' => 'INVALID_PAYLOAD',
        ]);
    }
}
