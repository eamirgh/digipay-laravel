# Callback & Verification

Confirming the payment with Digipay is **mandatory**. Purchases that are not verified by the merchant are automatically cancelled and refunded to the customer by Digipay's automated settlement engine.

---

## 1. Parsing the Callback

When a customer completes or abandons payment, Digipay sends an HTTP POST request to your `callbackUrl`.

Use `Digipay::parseCallback()` to automatically extract and normalize the payload:

```php
use Eamirgh\Digipay\Facades\Digipay;
use Illuminate\Http\Request;

public function handleCallback(Request $request)
{
    // Auto-detects from Illuminate request
    $callback = Digipay::parseCallback($request);

    // Or pass array:
    // $callback = Digipay::parseCallback($request->all());

    if (! $callback->isSuccessful()) {
        // Customer canceled transaction or PSP rejected card
        return redirect()->route('payment.failed');
    }

    $amount = $callback->amount;             // Transaction amount in Rials
    $providerId = $callback->providerId;     // Your internal order ID
    $trackingCode = $callback->trackingCode; // Digipay tracking code
    $rrn = $callback->rrn;                   // Bank Retrieval Reference Number
    $isCredit = $callback->isCredit;         // Was transaction paid via credit?
    $pspCode = $callback->getPspCode();      // PSP code e.g. "001" (SAMAN)
    $pspName = $callback->getPspName();      // PSP name e.g. "SAMAN"
}
```

---

## 2. Calling Verification

Always invoke `Digipay::verify()` after validating the amount and order identity:

```php
use Eamirgh\Digipay\Facades\Digipay;
use Eamirgh\Digipay\Exceptions\DigipayApiException;

try {
    $verification = Digipay::verify(
        trackingCodeOrRequest: $callback->trackingCode,
        providerId: $callback->providerId
    );

    if ($verification->isSuccessful()) {
        $trackingCode = $verification->trackingCode; // Verified Digipay tracking code
        $rrn = $verification->rrn;                   // Bank transaction RRN
        $cardPan = $verification->maskedPan;         // Masked PAN: "603799******1234"
        $pspName = $verification->pspName;           // "SAMAN", "MELLAT", etc.
        $gateway = $verification->paymentGateway;    // 0: IPG, 3: WALLET, 4: CPG

        // Transaction finalized! Mark order as paid.
    }
} catch (DigipayApiException $e) {
    // Handle error codes:
    // 9003: Purchase expired
    // 9009: Verification window expired
    // 9010: Verification failed
    // 9011: Verification undetermined
    logger()->error('Verification failed', [
        'status' => $e->digipayStatus,
        'message' => $e->getMessage(),
        'trackingCode' => $e->trackingCode,
    ]);
}
```

---

## 3. VerifyResponse Object Reference

| Property | Type | Description |
|---|---|---|
| `$verification->isSuccessful()` | `bool` | True if verified (`status === 0`) |
| `$verification->trackingCode` | `string` | Gateway tracking code |
| `$verification->providerId` | `string` | Merchant's order/provider identifier |
| `$verification->amount` | `int` | Confirmed payment amount (in Rials) |
| `$verification->rrn` | `?string` | Bank reference number (RRN) |
| `$verification->maskedPan` | `?string` | Masked customer card number (e.g. `603799******1234`) |
| `$verification->pspCode` | `?string` | PSP bank code (e.g. `001`) |
| `$verification->pspName` | `?string` | PSP bank name (e.g. `SAMAN`) |
| `$verification->paymentGateway` | `?int` | Payment channel (`0`: IPG, `3`: WALLET, `4`: CPG) |
| `$verification->terminalId` | `?string` | PSP terminal ID |
| `$verification->additionalInfo` | `array` | Prepayment, cash, and credit breakdown |
