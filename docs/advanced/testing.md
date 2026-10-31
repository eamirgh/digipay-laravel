# Testing & Mocking

`digipay-laravel` is built to be 100% test-friendly. Because it uses Laravel's HTTP Client (`Http::fake()`) internally, you can effortlessly test your payment flows without making actual network calls.

---

## Testing Ticket Creation in Your App

```php
namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_initiate_payment(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'total_amount' => 100000]);

        // Fake Digipay OAuth and Ticket endpoints
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'mock_token',
                'token_type' => 'bearer',
                'expires_in' => 3600,
            ], 200),

            '*/tickets/business*' => Http::response([
                'result' => ['status' => 0, 'message' => 'Success'],
                'ticket' => 'v2:test_ticket_123',
                'redirectUrl' => 'https://uatweb.mydigipay.info/web-pay/tgs/v2:test_ticket_123',
            ], 200),
        ]);

        $response = $this->actingAs($user)->post(route('payment.checkout', $order));

        // Assert redirect to Digipay gateway
        $response->assertRedirect('https://uatweb.mydigipay.info/web-pay/tgs/v2:test_ticket_123');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_ticket' => 'v2:test_ticket_123',
        ]);
    }
}
```

---

## Testing Gateway Callback & Verification

```php
public function test_order_is_verified_and_completed_on_callback(): void
{
    $order = Order::factory()->create([
        'total_amount' => 100000,
        'status' => 'pending_payment',
    ]);

    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'mock_token', 'expires_in' => 3600], 200),
        '*/purchases/verify*' => Http::response([
            'result' => ['status' => 0, 'message' => 'Success'],
            'trackingCode' => '10357963991735727353649',
            'providerId' => (string) $order->id,
            'amount' => 100000,
            'rrn' => '9988776655',
            'maskedPan' => '603799******1234',
            'pspName' => 'SAMAN',
        ], 200),
    ]);

    $callbackPayload = [
        'amount' => 100000,
        'providerId' => (string) $order->id,
        'trackingCode' => '10357963991735727353649',
        'result' => 'SUCCESS',
        'rrn' => '9988776655',
    ];

    $response = $this->post(route('payment.callback'), $callbackPayload);

    $response->assertRedirect(route('orders.show', $order));

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => 'paid',
        'tracking_code' => '10357963991735727353649',
        'rrn' => '9988776655',
    ]);
}
```

---

## Testing Events

You can also use Laravel's `Event::fake()`:

```php
use Eamirgh\Digipay\Events\PaymentVerified;
use Illuminate\Support\Facades\Event;

Event::fake([PaymentVerified::class]);

// Execute callback...

Event::assertDispatched(PaymentVerified::class, function ($event) use ($order) {
    return $event->response->providerId === (string) $order->id;
});
```
