# Multi-Store & Multi-Tenancy

For enterprise architectures, SaaS platforms, or multi-vendor marketplaces, `digipay-laravel` provides seamless multi-account management.

---

## 1. Static Configuration via Connections

Define named merchant accounts in `config/digipay.php`:

```php
return [
    'default' => 'main_shop',

    'connections' => [
        'main_shop' => [
            'sandbox' => false,
            'client_id' => env('MAIN_SHOP_DIGIPAY_CLIENT_ID'),
            'client_secret' => env('MAIN_SHOP_DIGIPAY_CLIENT_SECRET'),
            'username' => env('MAIN_SHOP_DIGIPAY_USERNAME'),
            'password' => env('MAIN_SHOP_DIGIPAY_PASSWORD'),
            'callback_url' => 'https://mainshop.com/callback',
        ],

        'secondary_branch' => [
            'sandbox' => false,
            'client_id' => env('BRANCH2_DIGIPAY_CLIENT_ID'),
            'client_secret' => env('BRANCH2_DIGIPAY_CLIENT_SECRET'),
            'username' => env('BRANCH2_DIGIPAY_USERNAME'),
            'password' => env('BRANCH2_DIGIPAY_PASSWORD'),
            'callback_url' => 'https://branch2.com/callback',
        ],
    ],
];
```

### Switching Connections

```php
use Eamirgh\Digipay\Facades\Digipay;

// Executes on default connection ('main_shop')
$ticket1 = Digipay::createTicket([...]);

// Explicitly execute on 'secondary_branch'
$ticket2 = Digipay::connection('secondary_branch')->createTicket([...]);

// Verify payment on secondary branch
$verify = Digipay::connection('secondary_branch')->verify($trackingCode, $providerId);
```

---

## 2. Dynamic Runtime Client Building

If you run a multi-tenant platform where merchants store their Digipay credentials in a database table:

```php
use Eamirgh\Digipay\Facades\Digipay;

$tenant = Tenant::findOrFail($tenantId);

$tenantClient = Digipay::build([
    'sandbox' => $tenant->is_sandbox,
    'client_id' => $tenant->digipay_client_id,
    'client_secret' => $tenant->digipay_client_secret,
    'username' => $tenant->digipay_username,
    'password' => $tenant->digipay_password,
    'callback_url' => route('tenant.payment.callback', $tenant),
]);

// Use dynamic client instance
$response = $tenantClient->createTicket([
    'amount' => 100000,
    'providerId' => 'TENANT_ORD_1',
]);

return $response->redirect();
```

Each tenant client safely isolates its OAuth cache token using a SHA-1 hash of its unique credentials, preventing any cache collisions across tenants.
