<?php

namespace Eamirgh\Digipay\Tests\Unit;

use Eamirgh\Digipay\DigipayClient;
use Eamirgh\Digipay\DTO\CallbackResult;
use Eamirgh\Digipay\DTO\VerifyResponse;
use Eamirgh\Digipay\Enums\TicketType;
use Eamirgh\Digipay\Exceptions\DigipayApiException;
use Eamirgh\Digipay\Exceptions\DigipayValidationException;
use Eamirgh\Digipay\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CallbackAndVerifyTest extends TestCase
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

    public function test_parse_callback_from_request_or_array(): void
    {
        /** @var DigipayClient $client */
        $client = $this->app->make(DigipayClient::class);

        $payload = [
            'amount' => 100000,
            'providerId' => 'ORDER_12345',
            'trackingCode' => '10357963991735727353649',
            'rrn' => '1234567890',
            'psp' => ['code' => '001', 'name' => 'SAMAN'],
            'isCredit' => false,
            'result' => 'SUCCESS',
            'type' => 0,
        ];

        $callback = $client->parseCallback($payload);

        $this->assertInstanceOf(CallbackResult::class, $callback);
        $this->assertTrue($callback->isSuccessful());
        $this->assertSame('ORDER_12345', $callback->providerId);
        $this->assertSame(100000, $callback->amount);
        $this->assertSame('10357963991735727353649', $callback->trackingCode);
        $this->assertSame('1234567890', $callback->rrn);
        $this->assertFalse($callback->isCredit);
        $this->assertSame('SUCCESS', $callback->result);
        $this->assertSame(0, $callback->type);

        // Also test from Illuminate Request
        $request = Request::create('/callback', 'POST', $payload);
        $callbackFromRequest = $client->parseCallback($request);
        $this->assertTrue($callbackFromRequest->isSuccessful());
        $this->assertSame('ORDER_12345', $callbackFromRequest->providerId);
    }

    public function test_parse_callback_failed_result(): void
    {
        /** @var DigipayClient $client */
        $client = $this->app->make(DigipayClient::class);

        $callback = $client->parseCallback([
            'amount' => 50000,
            'providerId' => 'ORDER_FAIL',
            'trackingCode' => '999',
            'result' => 'FAILURE',
        ]);

        $this->assertFalse($callback->isSuccessful());
        $this->assertSame('FAILURE', $callback->result);
    }

    public function test_verify_payment_success(): void
    {
        Http::fake([
            'https://uat.mydigipay.info/digipay/api/purchases/verify?type=11' => Http::response([
                'result' => ['status' => 0, 'message' => 'عملیات با موفقیت انجام شد', 'level' => 'INFO'],
                'trackingCode' => '10357963991735727353649',
                'providerId' => 'ORDER_12345',
                'amount' => 100000,
                'paymentGateway' => 0,
                'terminalId' => 'TERM123',
                'rrn' => '1234567890',
                'maskedPan' => '603799******1234',
                'pspCode' => '001',
                'pspName' => 'SAMAN',
                'fpCode' => '7',
                'fpName' => 'DIGIPAY',
                'additionalInfo' => [
                    'prepaymentAmount' => 0,
                    'cashAmount' => 0,
                    'creditAmount' => 100000,
                    'instantFinalization' => false,
                    'generateInvoice' => false,
                ],
            ], 200),
        ]);

        /** @var DigipayClient $client */
        $client = $this->app->make(DigipayClient::class);

        $response = $client->verify(
            trackingCodeOrRequest: '10357963991735727353649',
            providerId: 'ORDER_12345'
        );

        $this->assertInstanceOf(VerifyResponse::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertSame('10357963991735727353649', $response->trackingCode);
        $this->assertSame('ORDER_12345', $response->providerId);
        $this->assertSame(100000, $response->amount);
        $this->assertSame('603799******1234', $response->maskedPan);
        $this->assertSame('1234567890', $response->rrn);
        $this->assertSame('SAMAN', $response->pspName);
        $this->assertSame('001', $response->pspCode);
        $this->assertSame(0, $response->paymentGateway);

        Http::assertSent(function ($req) {
            return str_contains($req->url(), '/purchases/verify?type=11')
                && $req['trackingCode'] === '10357963991735727353649'
                && $req['providerId'] === 'ORDER_12345';
        });
    }

    public function test_verify_payment_with_custom_ticket_type(): void
    {
        Http::fake([
            'https://uat.mydigipay.info/digipay/api/purchases/verify?type=0' => Http::response([
                'result' => ['status' => 0, 'message' => 'Success'],
                'trackingCode' => 'TRACK_IPG',
                'providerId' => 'IPG_ORDER',
                'amount' => 200000,
            ], 200),
        ]);

        /** @var DigipayClient $client */
        $client = $this->app->make(DigipayClient::class);

        $response = $client->verify(
            trackingCodeOrRequest: 'TRACK_IPG',
            providerId: 'IPG_ORDER',
            type: TicketType::IPG
        );

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('TRACK_IPG', $response->trackingCode);

        Http::assertSent(function ($req) {
            return str_contains($req->url(), '/purchases/verify?type=0');
        });
    }

    public function test_verify_validation_exception_on_missing_fields(): void
    {
        /** @var DigipayClient $client */
        $client = $this->app->make(DigipayClient::class);

        $this->expectException(DigipayValidationException::class);
        $client->verify('', '');
    }

    public function test_verify_handles_error_response(): void
    {
        Http::fake([
            'https://uat.mydigipay.info/digipay/api/purchases/verify?type=11' => Http::response([
                'result' => [
                    'status' => 9010,
                    'message' => 'تایید پرداخت با خطا مواجه شد',
                    'level' => 'ERROR',
                ],
                'trackingCode' => '10357963991735727353649',
            ], 422),
        ]);

        /** @var DigipayClient $client */
        $client = $this->app->make(DigipayClient::class);

        $this->expectException(DigipayApiException::class);
        $this->expectExceptionCode(422);

        $client->verify('10357963991735727353649', 'ORDER_12345');
    }
}
