# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A Laravel package (not a standalone app) that wraps the Paystack payment gateway HTTP API. It is published to Packagist as `unicodeveloper/laravel-paystack`. There is no application bootstrap, routes, or database — consuming Laravel apps install it via Composer and use it through a facade or helper.

## Commands

```bash
composer install          # install dependencies
composer test             # run the full test suite (vendor/bin/phpunit)
vendor/bin/phpunit --filter testAllCustomersAreReturned   # run a single test by method name
```

There is no linter configured and no build step.

## Architecture

The package exposes a single service class behind a Laravel container binding.

- **`src/Paystack.php`** — the entire public API. Every method is a thin wrapper that builds a payload, fires a Guzzle request via the private `setHttpResponse($relativeUrl, $method, $body)`, and returns either `$this` (fluent), the decoded `getResponse()` array, or just the `getData()` portion. The base URL, secret key, and Guzzle client (with the `Bearer` auth header) are configured in the constructor; methods that issue follow-up requests call `setRequestOptions()` again to rebuild the client.
- **`src/PaystackServiceProvider.php`** — binds the singleton to the container key **`laravel-paystack`** and publishes the config file. Auto-discovered via the `extra.laravel` block in `composer.json`.
- **`src/Facades/Paystack.php`** — facade resolving to the `laravel-paystack` binding (`Paystack::getAuthorizationUrl()`, etc.).
- **`src/Support/helpers.php`** — global `paystack()` helper (autoloaded via `composer.json` `files`), equivalent to resolving the binding.
- **`src/TransRef.php`** — static, cryptographically-secure transaction reference generator (`openssl_random_pseudo_bytes`), reached through `Paystack::genTranxRef()`.
- **`src/Exceptions/`** — `IsNullException` and `PaymentVerificationFailedException`.
- **`resources/config/paystack.php`** — the publishable config; reads keys from env (`PAYSTACK_PUBLIC_KEY`, `PAYSTACK_SECRET_KEY`, `PAYSTACK_PAYMENT_URL`, `MERCHANT_EMAIL`).

### Key convention: payloads are read from the request

Most methods take **no arguments** and pull their payload straight from the global `request()` helper (e.g. `makePaymentRequest()`, `createPlan()`, `createSubscription()`). This assumes the consuming app POSTs form fields with specific names (`amount`, `email`, `reference`, `metadata`, `split`, `subaccount`, …). When adding or changing an endpoint method, follow this pattern: read fields via `request()->field`, then call `setRequestOptions()` and `setHttpResponse()`. Several methods (like `makePaymentRequest` and `createCustomer`) also accept an optional `$data` array to bypass the request entirely — preserve that escape hatch when present, since it supports API/headless use.

Amounts are integers in kobo (`intval(request()->amount)`), and the typical checkout flow is `getAuthorizationUrl()->redirectNow()`, then later `isTransactionVerificationValid()` / `getPaymentData()` against the `trxref` query param.

## Tests

`tests/PaystackTest.php` and `tests/HelpersTest.php` use plain `PHPUnit\Framework\TestCase` with **Mockery** — they mock the `Paystack` class / Guzzle `Client` rather than hitting the real API, so they assert shapes and call expectations, not live behavior. No Laravel test harness (Orchestra Testbench) is in use, so `Config`/`request()` are mocked where needed.

## Compatibility constraint

`composer.json` targets **Laravel 12 and 13 only**: PHP `^8.3` (the floor Laravel 13 requires), Illuminate `^12.0|^13.0`, Guzzle `^7.8|^8.0`, PHPUnit `^11.5|^12.0`. `phpunit.xml.dist` uses the PHPUnit 11/12 schema (`<source>`, no legacy `convert*`/`whitelist` attributes) — don't reintroduce the old format. PHP 8.3+ syntax is fair game.
