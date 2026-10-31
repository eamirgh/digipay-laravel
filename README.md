# Digipay UPG for Laravel 11, 12, 13+

[![Latest Version on Packagist](https://img.shields.io/packagist/v/eamirgh/digipay-laravel.svg?style=flat-square)](https://packagist.org/packages/eamirgh/digipay-laravel)
[![Total Downloads](https://img.shields.io/packagist/dt/eamirgh/digipay-laravel.svg?style=flat-square)](https://packagist.org/packages/eamirgh/digipay-laravel)
[![License](https://img.shields.io/packagist/l/eamirgh/digipay-laravel.svg?style=flat-square)](LICENSE)

Production-grade, scalable Laravel package for Digipay UPG (Unified Payment Gateway). Built with Test-Driven Development (TDD) for high-concurrency applications, multi-store/multi-tenant platforms, and enterprise Iranian financial workflows.

---

## Features

- **Laravel 11, 12, 13+ & PHP 8.2 - 8.4** compatibility.
- **Full Digipay UPG API Coverage**:
  - OAuth2 Authentication with high-performance cached token reuse and auto-refresh.
  - Ticket Creation (IPG, Wallet, Credit, BNPL, Credit Card).
  - Web & App Payment Redirection.
  - Callback Verification & Validation.
  - Manual Payment Reversal (within 25 min window).
  - Credit / BNPL Delivery Confirmation.
  - Full & Partial Refunds + Refund Tracking.
- **Enterprise Multi-Connection**: Switch credentials per store or build dynamic connections on the fly.
- **Decoupled Event-Driven Architecture**: Emits `TicketCreated`, `PaymentVerified`, `PaymentReversed`, `PaymentRefunded`.
- **Resilient Network Layer**: Automatic retries, configurable connect & read timeouts, and request pooling.
- **100% Test-Driven**: Complete test suite using Orchestra Testbench and PHPUnit 11.

---

## Installation

Install via Composer:

```bash
composer require eamirgh/digipay-laravel
```

Publish configuration:

```bash
php artisan vendor:publish --tag=digipay-config
```

---

## Environment Configuration

Add the following to your `.env` file:

```dotenv
DIGIPAY_SANDBOX=true # Set false for production
DIGIPAY_CLIENT_ID=your_client_id
DIGIPAY_CLIENT_SECRET=your_client_secret
DIGIPAY_USERNAME=your_username
DIGIPAY_PASSWORD=your_password
DIGIPAY_DEFAULT_TYPE=11 # 0: IPG, 5: CREDIT, 11: WALLET, 13: BNPL, 24: CREDIT_CARD
DIGIPAY_CALLBACK_URL=https://yourdomain.com/payment/callback
```

---

## Eloquent Model Integration (`Payable` Interface)

Adapt your existing Eloquent models (`Order`, `Invoice`, `Subscription`, `Bill`) to work natively with Digipay:

```php
namespace App\Models;

use Eamirgh\Digipay\Contracts\Payable;
use Eamirgh\Digipay\Traits\InteractsWithDigipay;
use Illuminate\Database\Eloquent\Model;

class Order extends Model implements Payable
{
    use InteractsWithDigipay;

    // Optional: override amount if column is not `amount` or `total`
    public function getDigipayAmount(): int
    {
        return (int) $this->total_price; // in Rials
    }

    // Optional: provide customer phone number for gateway pre-fill
    public function getDigipayCellNumber(): ?string
    {
        return $this->user?->phone_number;
    }
}
```

### Initiate Payment Directly from Model:

```php
// Pay via Facade
$response = Digipay::pay($order);

// Or call model helper directly:
$response = $order->createDigipayTicket();

return $response->redirect();
```

### Verify Payment Directly from Model:

```php
$verification = $order->verifyDigipayPayment($callback->trackingCode);

if ($verification->isSuccessful()) {
    $order->update(['paid_at' => now(), 'rrn' => $verification->rrn]);
}
```

---

## Quick Start

### 1. Request Payment Ticket & Redirect

```php
use Eamirgh\Digipay\Facades\Digipay;
use Eamirgh\Digipay\DTO\TicketRequest;
use Eamirgh\Digipay\Enums\TicketType;

// Simple array syntax
$response = Digipay::createTicket([
    'amount' => 100000, // In Rials
    'providerId' => 'ORDER_12345',
    'cellNumber' => '09120000000',
]);

// Or using strongly-typed DTO
$request = (new TicketRequest(
    amount: 100000,
    providerId: 'ORDER_12345',
    cellNumber: '09120000000',
    callbackUrl: route('payment.callback')
))->withPreferredGateway(2); // 2: IPG, 0: WALLET

$response = Digipay::createTicket($request, TicketType::WALLET);

if ($response->isSuccessful()) {
    // Store $response->ticket in database
    return $response->redirect(); // Redirects customer to Digipay gateway
}
```

### 2. Handle Gateway Callback & Verify

Digipay posts the payment outcome to your `callback_url`:

```php
use Eamirgh\Digipay\Facades\Digipay;
use Illuminate\Http\Request;

public function handleCallback(Request $request)
{
    $callback = Digipay::parseCallback($request);

    if (! $callback->isSuccessful()) {
        // Customer canceled or gateway failed
        return redirect()->route('checkout.failed')->with('error', 'Payment failed.');
    }

    // Always verify with Digipay prior to fulfilling orders!
    try {
        $verification = Digipay::verify(
            trackingCodeOrRequest: $callback->trackingCode,
            providerId: $callback->providerId
        );

        if ($verification->isSuccessful()) {
            // Transaction verified
            $rrn = $verification->rrn;
            $cardPan = $verification->maskedPan;

            return redirect()->route('checkout.success', [
                'trackingCode' => $verification->trackingCode,
                'rrn' => $rrn,
            ]);
        }
    } catch (\Eamirgh\Digipay\Exceptions\DigipayApiException $e) {
        // Digipay error (status code, result message)
        logger()->error('Digipay verification failed', ['error' => $e->getMessage()]);
    }

    return redirect()->route('checkout.failed');
}
```

---

## Advanced Operations

### Manual Reversal (IPG / DPG only, within 25 mins)

```php
$reversal = Digipay::reverse(
    purchaseTrackingCodeOrRequest: '10357963991735727353649',
    providerId: 'ORDER_12345'
);

if ($reversal->isSuccessful()) {
    // Payment reversed
}
```

### Credit / BNPL Delivery Confirmation

Mandatory for CREDIT (`type = 5`) and BNPL (`type = 13`):

```php
$delivery = Digipay::deliver(
    trackingCodeOrRequest: '10357963991735727353649',
    deliveryDate: now()->getTimestampMs(),
    invoiceNumber: 'INV-9900',
    products: ['sku-laptop-1', 'sku-case-2'],
    type: \Eamirgh\Digipay\Enums\TicketType::BNPL
);
```

### Refund & Refund Tracking

```php
// Initiate refund
$refund = Digipay::refund(
    saleTrackingCodeOrRequest: '10357963991735727353649',
    amount: 100000,
    providerId: 'REFUND_REQ_001'
);

// Check refund status later
$track = Digipay::trackRefund('REFUND_REQ_001');

if ($track->isSuccessful()) {
    // Refund completed
} elseif ($track->isPending()) {
    // Waiting for settlement
}
```

---

## Multi-Store & Multi-Tenant Support

Configure multiple credentials in `config/digipay.php`:

```php
'connections' => [
    'second_store' => [
        'sandbox' => false,
        'client_id' => 'store_2_id',
        'client_secret' => 'store_2_secret',
        'username' => 'store_2_user',
        'password' => 'store_2_pass',
    ],
],
```

Use specific connections:

```php
$ticket = Digipay::connection('second_store')->createTicket([
    'amount' => 250000,
    'providerId' => 'STORE2_ORDER_1',
]);
```

Or build a dynamic client on the fly:

```php
$client = Digipay::build([
    'client_id' => $tenant->digipay_client_id,
    'client_secret' => $tenant->digipay_client_secret,
    'username' => $tenant->digipay_username,
    'password' => $tenant->digipay_password,
]);

$response = $client->createTicket(...);
```

---

## Event Listeners

Listen to dispatched domain events in your `EventServiceProvider`:

- `Eamirgh\Digipay\Events\TicketCreated`
- `Eamirgh\Digipay\Events\PaymentVerified`
- `Eamirgh\Digipay\Events\PaymentReversed`
- `Eamirgh\Digipay\Events\PaymentRefunded`

```php
use Eamirgh\Digipay\Events\PaymentVerified;

Event::listen(PaymentVerified::class, function (PaymentVerified $event) {
    $trackingCode = $event->response->trackingCode;
    $amount = $event->response->amount;
    // Update order status, credit user wallet, etc.
});
```

---

## Testing

Run unit & feature test suite:

```bash
composer test
# or
vendor/bin/phpunit
```

---

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
