---
name: idempotency
description: >
  Install, configure, and apply the Laravel Idempotency package in Laravel
  applications when you need retry-safe POST, PUT, or PATCH endpoints.
---

# Idempotency

Use this skill when a Laravel application needs retry-safe write requests, idempotency keys, or controller attributes.

## Primary Goal

- install and configure `wendelladriel/laravel-idempotency` correctly
- choose the smallest correct integration path for the app
- apply middleware, attributes, and scopes using the package's public API

## Workflow

### 1. Inspect the Laravel app context

- confirm the app is a Laravel project
- inspect the target routes or controllers that create or update data
- identify whether the endpoint is better served by route middleware or a controller attribute
- inspect the app cache driver when idempotency will be enabled in production, since atomic locks are required

### 2. Install and publish configuration when needed

- install the package with `composer require wendelladriel/laravel-idempotency`
- publish the config with `php artisan vendor:publish --tag=idempotency-config` when the app needs non-default behavior
- use these config keys when customization is required:
  - `idempotency.ttl`
  - `idempotency.required`
  - `idempotency.scope`
  - `idempotency.header`

### 3. Choose the integration style

Prefer route middleware when:

- only a few endpoints need idempotency
- the behavior belongs directly on a route definition
- the app already configures most middleware in the route files

Use the controller attribute when:

- the app groups related write actions inside a controller
- class-level defaults with method-level overrides make the intent clearer
- `only` or `except` targeting is useful

### 4. Apply route middleware

Use the package middleware class:

```php
use WendellAdriel\Idempotency\Http\Middleware\Idempotent;

Route::post('/orders', StoreOrderController::class)
    ->middleware(Idempotent::class);
```

When the endpoint needs overrides, use `Idempotent::using(...)`:

```php
Route::post('/payments', ChargePaymentController::class)
    ->middleware(Idempotent::using(
        ttl: 600,
        required: false,
        scope: \WendellAdriel\Idempotency\Enums\IdempotencyScope::Ip,
        header: 'X-Idempotency-Key',
    ));
```

### 5. Apply the controller attribute

Use the package attribute on classes or methods:

```php
use WendellAdriel\Idempotency\Attributes\Idempotent;

#[Idempotent]
class OrderController
{
    public function store()
    {
        // ...
    }
}
```

Use method-level attributes to override class-level defaults:

```php
use WendellAdriel\Idempotency\Enums\IdempotencyScope;

#[Idempotent]
class PaymentController
{
    #[Idempotent(ttl: 600, scope: IdempotencyScope::Global)]
    public function store()
    {
        // ...
    }
}
```

### 6. Choose the correct scope

- use `user` when authenticated users should not collide with each other; guests fall back to IP
- use `ip` for guest-heavy flows where IP segmentation is enough
- use `global` when the same key should replay across users or IPs

### 7. Support key generation

- generate a random key in app code with `WendellAdriel\Idempotency\Idempotency::key()` when the server needs to create keys

### 8. Verify the integration

- verify repeated `POST`, `PUT`, or `PATCH` requests with the same key replay the original response
- verify mismatched payloads return `422`
- verify in-flight duplicate requests return `409` with `Retry-After: 1`
- verify missing headers only pass through when `required` is disabled

## Rules, References, and Templates

Read before executing:

- no additional resource files for this skill

## Examples

- add idempotency to payment or order creation endpoints
- protect retryable webhook receivers

## Anti-patterns

- do not apply idempotency to read-only routes
- do not suggest unsupported scope values beyond `user`, `ip`, and `global`
- do not document package maintenance tasks here; keep the skill focused on package adoption in Laravel apps
- do not assume every production cache driver supports atomic locks without checking
