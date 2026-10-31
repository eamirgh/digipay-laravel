# Quick Start Tutorial

This guide shows you how to integrate Digipay into a standard Laravel application from start to finish in less than 5 minutes.

## 1. Setup Controller Routes

In `routes/web.php`:

```php
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::post('/checkout/{order}', [PaymentController::class, 'checkout'])->name('payment.checkout');
Route::any('/payment/callback', [PaymentController::class, 'callback'])->name('payment.callback');
```

## 2. Implement the Controller

Create `app/Http/Controllers/PaymentController.php`:

```php
namespace App\Http\Controllers;

use App\Models\Order;
use Eamirgh\Digipay\Enums\TicketType;
use Eamirgh\Digipay\Facades\Digipay;
use Eamirgh\Digipay\Exceptions\DigipayApiException;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Initiate payment and redirect customer to Digipay
     */
    public function checkout(Order $order)
    {
        // 1. Request a payment ticket
        $response = Digipay::createTicket([
            'amount' => $order->total_amount, // in Rials
            'providerId' => (string) $order->id,
            'cellNumber' => $order->user->phone,
            'callbackUrl' => route('payment.callback'),
        ], TicketType::WALLET);

        if (! $response->isSuccessful()) {
            return back()->with('error', 'Could not initiate gateway session.');
        }

        // 2. Persist the issued ticket on the order record
        $order->update([
            'payment_ticket' => $response->ticket,
            'status' => 'pending_payment',
        ]);

        // 3. Redirect user directly to Digipay portal
        return $response->redirect();
    }

    /**
     * Handle incoming gateway callback
     */
    public function callback(Request $request)
    {
        // 1. Parse Digipay callback payload
        $callback = Digipay::parseCallback($request);

        $order = Order::findOrFail($callback->providerId);

        // 2. Check if gateway report is successful
        if (! $callback->isSuccessful()) {
            $order->update(['status' => 'failed']);
            return redirect()->route('orders.show', $order)->with('error', 'Payment was canceled or failed.');
        }

        // 3. Prevent double-spending: Ensure amount matches expected order total
        if ((int) $callback->amount !== (int) $order->total_amount) {
            abort(400, 'Security violation: Amount mismatch detected.');
        }

        // 4. Verify transaction with Digipay
        try {
            $verification = Digipay::verify(
                trackingCodeOrRequest: $callback->trackingCode,
                providerId: (string) $order->id
            );

            if ($verification->isSuccessful()) {
                $order->update([
                    'status' => 'paid',
                    'tracking_code' => $verification->trackingCode,
                    'rrn' => $verification->rrn,
                    'card_pan' => $verification->maskedPan,
                    'paid_at' => now(),
                ]);

                return redirect()->route('orders.show', $order)->with('success', 'Payment completed successfully!');
            }
        } catch (DigipayApiException $e) {
            logger()->error('Digipay verification failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'status' => $e->digipayStatus,
            ]);
        }

        $order->update(['status' => 'verification_failed']);
        return redirect()->route('orders.show', $order)->with('error', 'Verification failed.');
    }
}
```
