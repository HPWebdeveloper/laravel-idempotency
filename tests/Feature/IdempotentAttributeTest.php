<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use WendellAdriel\Idempotency\Attributes\Idempotent;
use WendellAdriel\Idempotency\Enums\IdempotencyScope;
use WendellAdriel\Idempotency\Http\Middleware\Idempotent as IdempotentMiddleware;

test('attribute expands to the expected middleware string using config defaults', function (): void {
    $route = Route::post('/orders', [IdempotentAttributeTestController::class, 'store']);

    expect($route->controllerMiddleware())->toBe([
        IdempotentMiddleware::class . ':3600,1,user,Idempotency-Key,10',
    ]);
});

test('attribute passes custom options', function (): void {
    $route = Route::post('/orders', [IdempotentAttributeCustomTestController::class, 'store']);

    expect($route->controllerMiddleware())->toBe([
        IdempotentMiddleware::class . ':600,0,ip,X-Idempotency-Key,10',
    ]);
});

test('class level attribute applies to all methods', function (): void {
    $storeRoute = Route::post('/orders', [IdempotentAttributeClassLevelTestController::class, 'store']);
    $updateRoute = Route::put('/orders/{id}', [IdempotentAttributeClassLevelTestController::class, 'update']);

    expect($storeRoute->controllerMiddleware())->toBe([
        IdempotentMiddleware::class . ':3600,1,user,Idempotency-Key,10',
    ])->and($updateRoute->controllerMiddleware())->toBe([
        IdempotentMiddleware::class . ':3600,1,user,Idempotency-Key,10',
    ]);
});

test('method level attribute stacks with class level', function (): void {
    $storeRoute = Route::post('/orders', [IdempotentAttributeMethodOverrideTestController::class, 'store']);
    $updateRoute = Route::put('/orders/{id}', [IdempotentAttributeMethodOverrideTestController::class, 'update']);

    expect($storeRoute->controllerMiddleware())->toBe([
        IdempotentMiddleware::class . ':3600,1,user,Idempotency-Key,10',
        IdempotentMiddleware::class . ':600,1,ip,X-Idempotency-Key,10',
    ])->and($updateRoute->controllerMiddleware())->toBe([
        IdempotentMiddleware::class . ':3600,1,user,Idempotency-Key,10',
    ]);
});

test('only option filters methods', function (): void {
    $storeRoute = Route::post('/orders', [IdempotentAttributeOnlyTestController::class, 'store']);
    $updateRoute = Route::put('/orders/{id}', [IdempotentAttributeOnlyTestController::class, 'update']);

    expect($storeRoute->controllerMiddleware())->toBe([
        IdempotentMiddleware::class . ':3600,1,user,Idempotency-Key,10',
    ])->and($updateRoute->controllerMiddleware())->toBe([]);
});

test('except option filters methods', function (): void {
    $storeRoute = Route::post('/orders', [IdempotentAttributeExceptTestController::class, 'store']);
    $updateRoute = Route::put('/orders/{id}', [IdempotentAttributeExceptTestController::class, 'update']);

    expect($storeRoute->controllerMiddleware())->toBe([])
        ->and($updateRoute->controllerMiddleware())->toBe([
            IdempotentMiddleware::class . ':3600,1,user,Idempotency-Key,10',
        ]);
});

test('attribute omissions pull defaults from config', function (): void {
    config()->set('idempotency.ttl', 120);
    config()->set('idempotency.required', false);
    config()->set('idempotency.scope', 'global');
    config()->set('idempotency.header', 'X-Config-Idempotency-Key');

    $route = Route::post('/config-orders', [IdempotentAttributeTestController::class, 'store']);

    expect($route->controllerMiddleware())->toBe([
        IdempotentMiddleware::class . ':120,0,global,X-Config-Idempotency-Key,10',
    ]);
});

class IdempotentAttributeTestController
{
    #[Idempotent]
    public function store(): void {}
}

#[Idempotent(ttl: 600, required: false, scope: IdempotencyScope::Ip, header: 'X-Idempotency-Key')]
class IdempotentAttributeCustomTestController
{
    public function store(): void {}
}

#[Idempotent]
class IdempotentAttributeClassLevelTestController
{
    public function store(): void {}

    public function update(): void {}
}

#[Idempotent]
class IdempotentAttributeMethodOverrideTestController
{
    #[Idempotent(ttl: 600, scope: IdempotencyScope::Ip, header: 'X-Idempotency-Key')]
    public function store(): void {}

    public function update(): void {}
}

class IdempotentAttributeOnlyTestController
{
    #[Idempotent(only: ['store'])]
    public function store(): void {}

    public function update(): void {}
}

#[Idempotent(except: ['store'])]
class IdempotentAttributeExceptTestController
{
    public function store(): void {}

    public function update(): void {}
}
