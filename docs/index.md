---
layout: home

hero:
  name: "Digipay Laravel"
  text: "Production-Grade Digipay UPG for Modern Laravel"
  tagline: "Scalable, resilient Iranian payment gateway integration for Laravel 11, 12, 13+. Built test-first for high concurrency."
  actions:
    - theme: brand
      text: Get Started
      link: /guide/quick-start
    - theme: alt
      text: API Reference
      link: /api/ticket-creation
    - theme: alt
      text: View on GitHub
      link: https://github.com/eamirgh/digipay-laravel

features:
  - title: Modern Laravel & PHP
    details: Compatible with Laravel 11, 12, 13+ and PHP 8.2, 8.3, 8.4. Strict types, backed enums, and fluent builders.
  - title: High-Performance Token Management
    details: Intelligent OAuth2 token caching with safety buffer and automatic renewal. Zero unnecessary auth round-trips.
  - title: Full Digipay UPG Coverage
    details: Complete implementation of Tickets, Web/App Redirection, Callback, Verification, Reversals, Deliveries, and Refunds.
  - title: Multi-Tenant & Multi-Store
    details: Seamlessly configure separate credentials per store or instantiate dynamic clients at runtime for SaaS and marketplaces.
  - title: Event-Driven Core
    details: Dispatches TicketCreated, PaymentVerified, PaymentReversed, and PaymentRefunded events to decouple your billing domain.
  - title: 100% Test-Driven
    details: Thoroughly tested with PHPUnit 11 and Orchestra Testbench. Mock-friendly with built-in Laravel HTTP client fakes.
---
