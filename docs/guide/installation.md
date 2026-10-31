# Installation & Setup

## Install via Composer

Add the package to your Laravel project using Composer:

```bash
composer require eamirgh/digipay-laravel
```

The package automatically registers its service provider `Eamirgh\Digipay\DigipayServiceProvider` and facade alias `Digipay`.

## Publish Configuration

Publish the `config/digipay.php` configuration file:

```bash
php artisan vendor:publish --tag=digipay-config
```

## Configure Environment Variables

Add your Digipay merchant credentials to your `.env` file:

```dotenv
# Set to false in production to point to https://api.mydigipay.com/digipay/api
DIGIPAY_SANDBOX=true

# OAuth2 credentials provided by Digipay
DIGIPAY_CLIENT_ID=your_client_id
DIGIPAY_CLIENT_SECRET=your_client_secret
DIGIPAY_USERNAME=your_username
DIGIPAY_PASSWORD=your_password

# Default gateway ticket type (11: WALLET/UPG, 0: IPG, 5: CREDIT, 13: BNPL)
DIGIPAY_DEFAULT_TYPE=11

# Default callback URL to handle gateway redirects
DIGIPAY_CALLBACK_URL=https://example.com/payment/callback

# Network & resilience settings
DIGIPAY_TIMEOUT=30
DIGIPAY_CONNECT_TIMEOUT=10
DIGIPAY_RETRY_TIMES=2
DIGIPAY_RETRY_SLEEP=100

# Cache settings for token reuse
DIGIPAY_CACHE_STORE=null # null uses default cache driver (e.g. redis)
DIGIPAY_CACHE_PREFIX=digipay_oauth_token_
DIGIPAY_TOKEN_TTL_BUFFER=60
```
