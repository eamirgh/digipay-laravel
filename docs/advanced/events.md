# Domain Events

`digipay-laravel` uses an event-driven architecture to decouple payment gateway mechanics from your core business logic.

---

## Available Events

| Event | Dispatched When | Payload Properties |
|---|---|---|
| `Eamirgh\Digipay\Events\TicketCreated` | Ticket created successfully | `TicketRequest $request`, `TicketResponse $response` |
| `Eamirgh\Digipay\Events\PaymentVerified` | Payment verified successfully | `VerifyResponse $response` |
| `Eamirgh\Digipay\Events\PaymentReversed` | Payment reversed successfully | `ReverseResponse $response` |
| `Eamirgh\Digipay\Events\PaymentRefunded` | Refund created successfully | `RefundResponse $response` |

---

## Registering Listeners

In your `app/Providers/EventServiceProvider.php` (or application bootstrap):

```php
use Eamirgh\Digipay\Events\PaymentVerified;
use Eamirgh\Digipay\Events\PaymentRefunded;
use App\Listeners\FulfillPaidOrder;
use App\Listeners\ProcessRefundNotification;

protected $listen = [
    PaymentVerified::class => [
        FulfillPaidOrder::class,
    ],
    PaymentRefunded::class => [
        ProcessRefundNotification::class,
    ],
];
```

## Example Listener

```php
namespace App\Listeners;

use Eamirgh\Digipay\Events\PaymentVerified;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;

class FulfillPaidOrder implements ShouldQueue
{
    public function handle(PaymentVerified $event): void
    {
        $response = $event->response;

        $order = Order::find($response->providerId);
        if (! $order || $order->isPaid()) {
            return;
        }

        $order->markAsPaid(
            trackingCode: $response->trackingCode,
            rrn: $response->rrn,
            maskedPan: $response->maskedPan,
            pspName: $response->pspName
        );

        // Send confirmation SMS or invoice
    }
}
```
