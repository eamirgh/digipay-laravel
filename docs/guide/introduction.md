# Introduction

`eamirgh/digipay-laravel` is an enterprise-grade Laravel package designed to integrate with **Digipay UPG (Unified Payment Gateway)**.

Digipay UPG provides unified payment services across multiple payment channels including:
- **IPG (Internet Payment Gateway):** Traditional bank portal payment via debit cards.
- **Wallet:** Direct payment via Digipay user wallet.
- **Credit & BNPL (Buy Now Pay Later):** Installment-based payments requiring fulfillment confirmation.
- **Credit Card:** Advanced credit card facilities.

## Architectural Highlights

- **Connection Pool & Resiliency:** Utilizes Laravel's HTTP client with configurable connect timeouts, request timeouts, and automatic retry backoff.
- **OAuth Token Scalability:** Tokens are automatically cached in your application's configured cache store (Redis, Memcached, etc.) with a configurable safety buffer (`ttl_buffer`), preventing token endpoint congestion during high checkout bursts.
- **Zero Framework Bloat:** Adheres to minimalist, idiomatic Laravel package conventions without unnecessary wrapper bloat or artificial abstraction layers.
- **Clean DTOs:** Strongly-typed Data Transfer Objects for requests and responses (`TicketRequest`, `TicketResponse`, `VerifyResponse`, `RefundResponse`, etc.) that serialize directly to JSON.

## System Requirements

- **PHP:** `^8.2 || ^8.3 || ^8.4`
- **Laravel Framework:** `^11.0 || ^12.0 || ^13.0`
- **PHP Extensions:** `ext-curl`, `ext-json`, `ext-mbstring`
