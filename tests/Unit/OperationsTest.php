<?php

namespace Eamirgh\Digipay\Tests\Unit;

use Eamirgh\Digipay\DigipayClient;
use Eamirgh\Digipay\DTO\DeliverRequest;
use Eamirgh\Digipay\DTO\DeliverResponse;
use Eamirgh\Digipay\DTO\RefundRequest;
use Eamirgh\Digipay\DTO\RefundResponse;
use Eamirgh\Digipay\DTO\ReverseRequest;
use Eamirgh\Digipay\DTO\ReverseResponse;
use Eamirgh\Digipay\DTO\TrackRefundResponse;
use Eamirgh\Digipay\Enums\TicketType;
use Eamirgh\Digipay\Exceptions\DigipayApiException;
use Eamirgh\Digipay\Exceptions\DigipayValidationException;
use Eamirgh\Digipay\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class OperationsTest extends TestCase
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

    public function test_manual_reverse_success(): void
    {
        Http::fake([
            'https://uat.mydigipay.info/digipay/api/reverse?type=0' => Http::response([
                'result' => ['status' => 0, 'message' => 'عملیات با موفقیت انجام شد', 'level' => 'INFO'],
                'providerId' => 'ORDER_12345',
                'trackingCode' => '10357963991735727353649',
                'rrn' => '1234567890',
                'maskedPan' => '603799******1234',
                'amount' => 100000,
                'paymentGateway' => 0,
            ], 200),
        ]);

        /** @var DigipayClient $client */
        $client = $this->app->make(DigipayClient::class);

        $response = $client->reverse(
            purchaseTrackingCodeOrRequest: '10357963991735727353649',
            providerId: 'ORDER_12345',
            type: TicketType::IPG
        );

        $this->assertInstanceOf(ReverseResponse::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertSame('10357963991735727353649', $response->trackingCode);
        $this->assertSame('ORDER_12345', $response->providerId);
        $this->assertSame(100000, $response->amount);
        $this->assertSame('1234567890', $response->rrn);
        $this->assertSame('603799******1234', $response->maskedPan);

        Http::assertSent(function ($req) {
            return str_contains($req->url(), '/reverse?type=0')
                && $req['purchaseTrackingCode'] === '10357963991735727353649'
                && $req['providerId'] === 'ORDER_12345';
        });
    }

    public function test_reverse_with_dto(): void
    {
        Http::fake([
            'https://uat.mydigipay.info/digipay/api/reverse?type=11' => Http::response([
                'result' => ['status' => 0, 'message' => 'Success'],
                'providerId' => 'ORD_1',
                'trackingCode' => 'TRACK_1',
                'amount' => 50000,
            ], 200),
        ]);

        /** @var DigipayClient $client */
        $client = $this->app->make(DigipayClient::class);

        $dto = new ReverseRequest(purchaseTrackingCode: 'TRACK_1', providerId: 'ORD_1');
        $response = $client->reverse($dto);

        $this->assertTrue($response->isSuccessful());
        $this->assertSame('TRACK_1', $response->trackingCode);
    }

    public function test_deliver_purchase_success(): void
    {
        Http::fake([
            'https://uat.mydigipay.info/digipay/api/purchases/deliver?type=5' => Http::response([
                'result' => ['status' => 0, 'message' => 'عملیات با موفقیت انجام شد', 'level' => 'INFO'],
            ], 200),
        ]);

        /** @var DigipayClient $client */
        $client = $this->app->make(DigipayClient::class);

        $response = $client->deliver(
            trackingCodeOrRequest: '10357963991735727353649',
            deliveryDate: 1592502763000,
            invoiceNumber: 'INV-9900',
            products: ['sku-1', 'sku-2'],
            type: TicketType::CREDIT
        );

        $this->assertInstanceOf(DeliverResponse::class, $response);
        $this->assertTrue($response->isSuccessful());

        Http::assertSent(function ($req) {
            return str_contains($req->url(), '/purchases/deliver?type=5')
                && $req['trackingCode'] === '10357963991735727353649'
                && $req['deliveryDate'] === 1592502763000
                && $req['invoiceNumber'] === 'INV-9900'
                && $req['products'] === ['sku-1', 'sku-2'];
        });
    }

    public function test_deliver_with_dto(): void
    {
        Http::fake([
            'https://uat.mydigipay.info/digipay/api/purchases/deliver?type=13' => Http::response([
                'result' => ['status' => 0, 'message' => 'Success'],
            ], 200),
        ]);

        /** @var DigipayClient $client */
        $client = $this->app->make(DigipayClient::class);

        $dto = new DeliverRequest(
            trackingCode: 'TRACK_BNPL',
            deliveryDate: 1700000000000,
            invoiceNumber: 'INV-123',
            products: ['p1'],
            type: TicketType::BNPL
        );

        $response = $client->deliver($dto);
        $this->assertTrue($response->isSuccessful());
    }

    public function test_refund_purchase_success(): void
    {
        Http::fake([
            'https://uat.mydigipay.info/digipay/api/refunds?type=11' => Http::response([
                'result' => [
                    'title' => 'SUCCESS',
                    'status' => 0,
                    'message' => 'عملیات با موفقیت انجام شد',
                    'level' => 'INFO',
                ],
                'trackingCode' => 'REFUND_TRACK_999',
            ], 200),
        ]);

        /** @var DigipayClient $client */
        $client = $this->app->make(DigipayClient::class);

        $response = $client->refund(
            saleTrackingCodeOrRequest: '10357963991735727353649',
            amount: 100000,
            providerId: 'REFUND_UNIQUE_ID'
        );

        $this->assertInstanceOf(RefundResponse::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertSame('REFUND_TRACK_999', $response->trackingCode);
        $this->assertSame('REFUND_UNIQUE_ID', $response->providerId);
        $this->assertSame(100000, $response->amount);

        Http::assertSent(function ($req) {
            return str_contains($req->url(), '/refunds?type=11')
                && $req['saleTrackingCode'] === '10357963991735727353649'
                && $req['amount'] === 100000
                && $req['providerId'] === 'REFUND_UNIQUE_ID';
        });
    }

    public function test_refund_with_dto(): void
    {
        Http::fake([
            'https://uat.mydigipay.info/digipay/api/refunds?type=0' => Http::response([
                'result' => ['status' => 0, 'message' => 'Success'],
                'trackingCode' => 'REF_TRK_1',
            ], 200),
        ]);

        /** @var DigipayClient $client */
        $client = $this->app->make(DigipayClient::class);

        $dto = new RefundRequest(
            saleTrackingCode: 'SALE_1',
            amount: 50000,
            providerId: 'REF_ID_1',
            type: TicketType::IPG
        );

        $response = $client->refund($dto);
        $this->assertTrue($response->isSuccessful());
        $this->assertSame('REF_TRK_1', $response->trackingCode);
    }

    public function test_refund_validation_fails_when_amount_zero(): void
    {
        /** @var DigipayClient $client */
        $client = $this->app->make(DigipayClient::class);

        $this->expectException(DigipayValidationException::class);
        $client->refund('SALE_1', 0, 'REF_1');
    }

    public function test_track_refund_success(): void
    {
        Http::fake([
            'https://uat.mydigipay.info/digipay/api/refunds/REFUND_TRACK_999?type=11' => Http::response([
                'providerId' => 'REFUND_UNIQUE_ID',
                'trackingCode' => 'REFUND_TRACK_999',
                'status' => 0,
                'resultCode' => 0,
                'transferDate' => '1592502763000',
                'destinationType' => 0,
                'destination' => '603799******1234',
                'result' => [
                    'title' => 'SUCCESS',
                    'status' => 0,
                    'message' => 'عملیات با موفقیت انجام شد',
                    'level' => 'INFO',
                ],
            ], 200),
        ]);

        /** @var DigipayClient $client */
        $client = $this->app->make(DigipayClient::class);

        $response = $client->trackRefund('REFUND_TRACK_999');

        $this->assertInstanceOf(TrackRefundResponse::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertSame('REFUND_UNIQUE_ID', $response->providerId);
        $this->assertSame('REFUND_TRACK_999', $response->trackingCode);
        $this->assertSame(0, $response->status);
        $this->assertSame(0, $response->resultCode);
        $this->assertSame('1592502763000', $response->transferDate);
        $this->assertSame(0, $response->destinationType);
        $this->assertSame('603799******1234', $response->destination);

        Http::assertSent(function ($req) {
            return str_contains($req->url(), '/refunds/REFUND_TRACK_999?type=11');
        });
    }

    public function test_track_refund_validation_fails_on_empty_inquiry_id(): void
    {
        /** @var DigipayClient $client */
        $client = $this->app->make(DigipayClient::class);

        $this->expectException(DigipayValidationException::class);
        $client->trackRefund('');
    }
}
