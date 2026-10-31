# Ticket Creation & Redirect

Ticket creation initiates a payment session with Digipay and generates a unique gateway token along with a redirection URL.

- **Endpoint:** `POST /tickets/business?type={type}`
- **Ticket Types:**
  - `0`: IPG (Traditional payment portal)
  - `5`: CREDIT (Digipay Credit purchase)
  - `11`: WALLET / UPG General (Default)
  - `13`: BNPL (Buy Now Pay Later installment)
  - `24`: CREDIT-CARD

---

## Basic Usage

You can pass an array directly or use the strongly typed `TicketRequest` DTO:

### Passing an Array

```php
use Eamirgh\Digipay\Facades\Digipay;
use Eamirgh\Digipay\Enums\TicketType;

$response = Digipay::createTicket([
    'amount' => 500000, // 500,000 Rials (50,000 Tomans)
    'providerId' => 'INVOICE_8829',
    'cellNumber' => '09121112233',
    'callbackUrl' => 'https://merchant.example/callback',
], TicketType::WALLET);

if ($response->isSuccessful()) {
    $ticket = $response->ticket;           // e.g. "v2:ab17ec383d654be3b009f9fc45202f80"
    $redirectUrl = $response->redirectUrl; // e.g. "https://uatweb.mydigipay.info/web-pay/tgs/v2:..."

    // Return Laravel RedirectResponse
    return $response->redirect();
}
```

---

## Fluent DTO with Advanced Options

For multi-item shopping baskets, gateway routing, and split settlement details:

```php
use Eamirgh\Digipay\Facades\Digipay;
use Eamirgh\Digipay\DTO\TicketRequest;
use Eamirgh\Digipay\Enums\TicketType;
use Eamirgh\Digipay\Enums\PreferredGateway;
use Eamirgh\Digipay\Enums\ProductType;

$request = (new TicketRequest(
    amount: 1500000,
    providerId: 'ORDER_99182',
    cellNumber: '09120000000',
    callbackUrl: route('payment.callback')
))
    // 0: WALLET, 2: IPG
    ->withPreferredGateway(PreferredGateway::IPG->value)

    // Shopping basket breakdown
    ->withBasket(basketId: 'BASKET_554', items: [
        [
            'sellerId' => 'SELLER_10',
            'supplierId' => 'SUPPLIER_20',
            'productCode' => 'PHONE_15_PRO',
            'brand' => 'Apple',
            'productType' => ProductType::DURABLE->value,
            'count' => 1,
            'categoryId' => 'Mobile',
        ],
    ])

    // Multi-party split payment (max 2 entries)
    ->withSplits([
        [
            'type' => 'simple',
            'amount' => 500000,
            'username' => 'partner_merchant_username',
        ],
    ]);

$response = Digipay::createTicket($request, TicketType::WALLET);

return $response->redirect();
```

---

## Response Object Reference

The returned `Eamirgh\Digipay\DTO\TicketResponse` provides:

| Property / Method | Type | Description |
|---|---|---|
| `$response->ticket` | `string` | The issued gateway ticket identifier |
| `$response->redirectUrl` | `string` | Gateway URL for the customer to visit |
| `$response->providerId` | `?string` | Merchant's order/provider identifier |
| `$response->amount` | `?int` | Payment amount in Rials |
| `$response->isSuccessful()` | `bool` | Returns `true` if `status === 0` |
| `$response->getStatus()` | `?int` | Digipay status code (`0` = success) |
| `$response->getMessage()` | `?string` | Gateway localized feedback message |
| `$response->redirect()` | `RedirectResponse` | Generates a Laravel redirect away to the gateway |
| `$response->toArray()` | `array` | Serializes response to an array |
