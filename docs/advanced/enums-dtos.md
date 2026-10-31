# Enums & DTO Reference

The package provides backed enums and structured Data Transfer Objects for complete type safety.

---

## Enums

### `TicketType` (`Eamirgh\Digipay\Enums\TicketType`)
- `TicketType::IPG` (`0`): Internet Payment Gateway
- `TicketType::CREDIT` (`5`): Digipay Credit purchase
- `TicketType::WALLET` (`11`): Digipay Wallet / general UPG ticket (Default)
- `TicketType::BNPL` (`13`): Buy Now Pay Later
- `TicketType::CREDIT_CARD` (`24`): Credit Card

### `PaymentGateway` (`Eamirgh\Digipay\Enums\PaymentGateway`)
- `PaymentGateway::IPG` (`0`)
- `PaymentGateway::WALLET` (`3`)
- `PaymentGateway::CPG` (`4`)

### `PreferredGateway` (`Eamirgh\Digipay\Enums\PreferredGateway`)
- `PreferredGateway::WALLET` (`0`)
- `PreferredGateway::IPG` (`2`)

### `ProductType` (`Eamirgh\Digipay\Enums\ProductType`)
- `ProductType::DURABLE` (`1`)
- `ProductType::CONSUMABLE` (`2`)
- `ProductType::SERVICE` (`3`)
- `ProductType::DURABLE_CONSUMABLE` (`4`)

### `DestinationType` (`Eamirgh\Digipay\Enums\DestinationType`)
- `DestinationType::MASKED_PAN` (`0`)
- `DestinationType::IBAN` (`1`)
- `DestinationType::WALLET` (`2`)
- `DestinationType::CREDIT` (`3`)

### `PspCode` (`Eamirgh\Digipay\Enums\PspCode`)
- `PspCode::SAMAN` (`'001'`)
- `PspCode::PARSIAN` (`'002'`)
- `PspCode::MELLAT` (`'003'`)
- `PspCode::ENOVIN` (`'004'`)
- `PspCode::PASARGAD` (`'005'`)
- `PspCode::FANAVA` (`'006'`)
- `PspCode::MELLI` (`'007'`)
- `PspCode::IRKISH` (`'008'`)

---

## Data Transfer Objects (DTOs)

All DTOs implement `Illuminate\Contracts\Support\Arrayable`:

- `TicketRequest`: Request builder for ticket issuance (`amount`, `providerId`, `cellNumber`, `callbackUrl`, `additionalInfo`, `basketDetailsDto`, `splitDetailsList`).
- `TicketResponse`: Response from ticket creation with `redirect()` helper.
- `CallbackResult`: Normalized object representing the incoming gateway webhook callback.
- `VerifyRequest`: Strongly-typed verification payload (`trackingCode`, `providerId`, `type`).
- `VerifyResponse`: Full payment confirmation details (`amount`, `maskedPan`, `rrn`, `pspCode`, `pspName`, etc.).
- `ReverseRequest` & `ReverseResponse`: Reversal request and response details.
- `DeliverRequest` & `DeliverResponse`: Delivery confirmation for Credit/BNPL.
- `RefundRequest` & `RefundResponse`: Refund request and tracking code.
- `TrackRefundResponse`: Inquiry response with `status`, `transferDate`, `destination`, and `isPending()` / `isSuccessful()` helpers.
