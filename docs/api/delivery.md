# Delivery Confirmation

Delivery confirmation confirms order fulfillment.

::: info When is this mandatory?
Delivery confirmation is **mandatory for CREDIT (`type: 5`) and BNPL (`type: 13`) transactions**.

Without this confirmation, credit provider accounts will not settle funds to the merchant.
:::

- **Endpoint:** `POST /purchases/deliver?type={type}`

---

## Example

```php
use Eamirgh\Digipay\Facades\Digipay;
use Eamirgh\Digipay\Enums\TicketType;
use Eamirgh\Digipay\DTO\DeliverRequest;

// Using helper parameters
$response = Digipay::deliver(
    trackingCodeOrRequest: '10357963991735727353649',
    deliveryDate: now()->getTimestampMs(), // Milliseconds timestamp
    invoiceNumber: 'INV-9900',
    products: ['sku-macbook-m3', 'sku-usb-dongle'],
    type: TicketType::CREDIT
);

if ($response->isSuccessful()) {
    // Delivery confirmed with Digipay Credit/BNPL division
}

// Or using DTO
$dto = new DeliverRequest(
    trackingCode: '10357963991735727353649',
    deliveryDate: now()->getTimestampMs(),
    invoiceNumber: 'INV-9900',
    products: ['sku-macbook-m3'],
    type: TicketType::BNPL
);

$response = Digipay::deliver($dto);
```

---

## DeliverResponse Object Reference

| Property | Type | Description |
|---|---|---|
| `$response->isSuccessful()` | `bool` | True if delivery confirmation succeeded (`status === 0`) |
| `$response->result` | `array` | Status, title, and localized message |
| `$response->raw` | `array` | Complete raw response |
