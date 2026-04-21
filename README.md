# <div align="center">
#     <img src="https://github.com/WendellAdriel/laravel-idempotency/raw/main/art/logo.png" alt="Laravel Idempotency" height="300"/>
#     <p>
#         <h1>Laravel Idempotency</h1>
#         HTTP Idempotency Middleware for Laravel applications
#     </p>
# </div>
# 
# <p align="center">
#     <a href="https://packagist.org/packages/wendelladriel/laravel-idempotency"><img src="https://img.shields.io/packagist/v/wendelladriel/laravel-idempotency.svg?style=flat-square" alt="Packagist"></a>
#     <a href="https://packagist.org/packages/wendelladriel/laravel-idempotency"><img src="https://img.shields.io/packagist/php-v/wendelladriel/laravel-idempotency.svg?style=flat-square" alt="PHP from Packagist"></a>
#     <a href="https://packagist.org/packages/wendelladriel/laravel-idempotency"><img src="https://badge.laravel.cloud/badge/wendelladriel/laravel-idempotency?style=flat" alt="Laravel versions"></a>
#     <a href="https://github.com/WendellAdriel/laravel-idempotency/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/WendellAdriel/laravel-idempotency/tests.yml?branch=main&label=Tests"></a>
# </p>
# 
# ## Installation
# 
# ```bash
# composer require wendelladriel/laravel-idempotency
# ```
# 
# ## Config
# 
# Publish the config file:
# 
# ```bash
# php artisan vendor:publish --provider="WendellAdriel\Idempotency\Providers\IdempotencyServiceProvider" --tag=config
# ```
# 
# The config file will be added to `config/idempotency.php`.
# 
# ## Testing
# 
# ```bash
# composer test
# ```
# 
# ## Credits
# 
# - [Wendell Adriel](https://github.com/WendellAdriel)
# - [All Contributors](../../contributors)
# 
# ## Contributing
# 
# Check the **[Contributing Guide](CONTRIBUTING.md)**.
