<?php

namespace Eamirgh\Digipay\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Eamirgh\Digipay\DigipayClient connection(?string $name = null)
 * @method static \Eamirgh\Digipay\DigipayClient build(array $config)
 * @method static \Eamirgh\Digipay\DigipayClient setConfig(array $config)
 * @method static mixed getConfig(?string $key = null, mixed $default = null)
 * @method static string getAccessToken(bool $forceRefresh = false)
 * @method static \Eamirgh\Digipay\DTO\TicketResponse pay(\Eamirgh\Digipay\Contracts\Payable $payable, ?int $type = null)
 * @method static \Eamirgh\Digipay\DTO\TicketResponse createTicket(\Eamirgh\Digipay\DTO\TicketRequest|array $request, ?int $type = null)
 * @method static \Eamirgh\Digipay\DTO\VerifyResponse verifyPayable(\Eamirgh\Digipay\Contracts\Payable $payable, ?string $trackingCode = null, ?int $type = null)
 * @method static \Eamirgh\Digipay\DTO\VerifyResponse verify(string|\Eamirgh\Digipay\DTO\VerifyRequest|array $trackingCodeOrRequest, ?string $providerId = null, ?int $type = null)
 * @method static \Eamirgh\Digipay\DTO\ReverseResponse reverse(string|\Eamirgh\Digipay\DTO\ReverseRequest|array $purchaseTrackingCodeOrRequest, ?string $providerId = null, ?int $type = null)
 * @method static \Eamirgh\Digipay\DTO\DeliverResponse deliver(string|\Eamirgh\Digipay\DTO\DeliverRequest|array $trackingCodeOrRequest, ?int $deliveryDate = null, ?string $invoiceNumber = null, array $products = [], ?int $type = null)
 * @method static \Eamirgh\Digipay\DTO\RefundResponse refund(string|\Eamirgh\Digipay\DTO\RefundRequest|array $saleTrackingCodeOrRequest, ?int $amount = null, ?string $providerId = null, ?int $type = null)
 * @method static \Eamirgh\Digipay\DTO\TrackRefundResponse trackRefund(string $inquiryId, ?int $type = null)
 * @method static \Eamirgh\Digipay\DTO\CallbackResult parseCallback(\Illuminate\Http\Request|array|null $request = null)
 *
 * @see \Eamirgh\Digipay\DigipayManager
 * @see \Eamirgh\Digipay\DigipayClient
 */
class Digipay extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'digipay';
    }
}
