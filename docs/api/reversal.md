# Manual Payment Reversal

Manual reversal allows a merchant to roll back a verified purchase.

::: warning Strict Constraints
- **Window:** Must be requested within **25 minutes** of the original payment.
- **Supported Gateways:** Applicable only to **IPG (Internet Payment Gateway)** and **DPG**. Not applicable to direct wallet or BNPL payments.
- **Mutual Exclusivity:** Manual reversal and refund are mutually exclusive for a given purchase.
:::

- **Endpoint:** `POST /reverse?type={type}`

---

## Example

```php
use Eamirgh\Digipay\Facades\Digipay;
use Eamirgh\Digipay\Enums\TicketType;
use Eamirgh\Digipay\Exceptions\DigipayApiException;

try {
    $response = Digipay::reverse(
        purchaseTrackingCodeOrRequest: '10357963991735727353649',
        providerId: 'ORDER_12345',
        type: TicketType::IPG
    );

    if ($response->isSuccessful()) {
        $amount = $response->amount;
        $trackingCode = $response->trackingCode;
        $rrn = $response->rrn;
        $maskedPan = $response->maskedPan;

        // Payment successfully reversed and credited back to customer card.
    }
} catch (DigipayApiException $e) {
    logger()->error('Payment reversal failed', [
        'error' => $e->getMessage(),
        'status' => $e->digipayStatus,
    ]);
}
```

---

## ReverseResponse Object Reference

| Property | Type | Description |
|---|---|---|
| `$response->isSuccessful()` | `bool` | True if reversal succeeded |
| `$response->trackingCode` | `string` | Purchase tracking code |
| `$response->providerId` | `string` | Merchant provider/order ID |
| `$response->amount` | `int` | Reversed amount in Rials |
| `$response->rrn` | `?string` | Bank reference number |
| `$response->maskedPan` | `?string` | Masked customer card number |
| `$response->paymentGateway` | `?int` | Gateway type |
| `$response->raw` | `array` | Full raw API response payload |
