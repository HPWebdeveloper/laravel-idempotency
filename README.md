# Laravel Idempotency

Handle idempotent operations in Laravel applications.

## Installation

```bash
composer require wendelladriel/laravel-idempotency
```

## Config

Publish the config file:

```bash
php artisan vendor:publish --provider="WendellAdriel\Idempotency\Providers\IdempotencyServiceProvider" --tag=config
```

The config file will be added to `config/idempotency.php`.

## Testing

```bash
composer test
```

## Credits

- [Wendell Adriel](https://github.com/WendellAdriel)
- [All Contributors](../../contributors)

## Contributing

Check the [Contributing Guide](CONTRIBUTING.md).
