# Eloquent Model Integration

`digipay-laravel` allows your host application models (such as `Order`, `Invoice`, `Subscription`, or `Bill`) to integrate natively with Digipay via the `Eamirgh\Digipay\Contracts\Payable` interface and `Eamirgh\Digipay\Traits\InteractsWithDigipay` trait.

---

## 1. Implement `Payable` on Your Model

```php
namespace App\Models;

use Eamirgh\Digipay\Contracts\Payable;
use Eamirgh\Digipay\Traits\InteractsWithDigipay;
use Eamirgh\Digipay\Enums\TicketType;
use Illuminate\Database\Eloquent\Model;

class Order extends Model implements Payable
{
    use InteractsWithDigipay;

    protected $guarded = [];

    /**
     * Required: Total amount in Iranian Rials
     */
    public function getDigipayAmount(): int
    {
        return (int) $this->total_amount;
    }

    /**
     * Optional: Customer cell phone number for gateway pre-fill
     */
    public function getDigipayCellNumber(): ?string
    {
        return $this->customer_phone;
    }

    /**
     * Optional: Custom callback URL for this order
     */
    public function getDigipayCallbackUrl(): ?string
    {
        return route('order.payment.callback', $this);
    }

    /**
     * Optional: Default preferred gateway type for this model
     */
    public function getDigipayType(): TicketType|int|null
    {
        return TicketType::WALLET;
    }
}
```

The `InteractsWithDigipay` trait provides default implementations for all methods:
- `getDigipayProviderId()`: Defaults to `(string) $this->getKey()`
- `getDigipayAmount()`: Defaults to `$this->amount ?? $this->total_amount ?? $this->total ?? 0`
- `getDigipayCellNumber()`: Defaults to `$this->cell_number ?? $this->phone ?? $this->mobile ?? null`
- `getDigipayCallbackUrl()`: Defaults to `null` (uses global `config('digipay.callback_url')`)
- `getDigipayAdditionalInfo()`: Defaults to `[]`
- `getDigipayBasketDetails()`: Defaults to `null`
- `getDigipaySplitDetails()`: Defaults to `null`

---

## 2. Initiating Payments from Models

You can initiate payments in two convenient ways:

### Via the Model Instance:

```php
// In your controller:
public function checkout(Order $order)
{
    // Generates ticket and returns TicketResponse
    $response = $order->createDigipayTicket();

    // Or specify a ticket type:
    // $response = $order->createDigipayTicket(TicketType::BNPL);

    return $response->redirect();
}
```

### Via the `Digipay` Facade:

```php
use Eamirgh\Digipay\Facades\Digipay;

$response = Digipay::pay($order);

return $response->redirect();
```

---

## 3. Verifying Payments with Models

When handling the callback, verify the transaction directly on the model:

```php
public function callback(Request $request, Order $order)
{
    $callback = Digipay::parseCallback($request);

    if (! $callback->isSuccessful()) {
        $order->update(['status' => 'failed']);
        return redirect()->route('orders.show', $order);
    }

    // Verify using the model helper
    $verification = $order->verifyDigipayPayment($callback->trackingCode);

    if ($verification->isSuccessful()) {
        $order->update([
            'status' => 'paid',
            'tracking_code' => $verification->trackingCode,
            'rrn' => $verification->rrn,
            'card_pan' => $verification->maskedPan,
        ]);

        return redirect()->route('orders.show', $order)->with('success', 'Order paid!');
    }
}
```

---

## 4. Automatic Event Binding

When you pass a `Payable` model into `pay()` or `verifyPayable()`, the dispatched domain events automatically attach your model instance!

```php
namespace App\Listeners;

use Eamirgh\Digipay\Events\PaymentVerified;

class UpdateOrderState
{
    public function handle(PaymentVerified $event): void
    {
        // Access the original Payable model directly:
        $order = $event->payable;

        if ($order instanceof \App\Models\Order) {
            $order->update([
                'paid_at' => now(),
                'rrn' => $event->response->rrn,
            ]);
        }
    }
}
```
