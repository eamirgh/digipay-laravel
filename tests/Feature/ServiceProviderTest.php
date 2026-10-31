<?php

namespace Eamirgh\Digipay\Tests\Feature;

use Eamirgh\Digipay\DigipayClient;
use Eamirgh\Digipay\DigipayManager;
use Eamirgh\Digipay\Facades\Digipay;
use Eamirgh\Digipay\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function test_service_provider_registers_digipay_client_and_manager(): void
    {
        $this->assertTrue($this->app->bound('digipay'));
        $this->assertTrue($this->app->bound(DigipayManager::class));
        $this->assertTrue($this->app->bound(DigipayClient::class));

        $manager = $this->app->make('digipay');
        $this->assertInstanceOf(DigipayManager::class, $manager);

        $client = $this->app->make(DigipayClient::class);
        $this->assertInstanceOf(DigipayClient::class, $client);
    }

    public function test_facade_resolves_manager_and_proxies_to_client(): void
    {
        $this->assertInstanceOf(DigipayManager::class, Digipay::getFacadeRoot());
        $this->assertInstanceOf(DigipayClient::class, Digipay::connection());
        $this->assertSame('test_client_id', Digipay::getConfig('client_id'));
    }

    public function test_config_is_loaded(): void
    {
        $this->assertSame('test_client_id', config('digipay.client_id'));
        $this->assertTrue(config('digipay.sandbox'));
    }

    public function test_config_can_be_published(): void
    {
        $this->artisan('vendor:publish', ['--tag' => 'digipay-config'])
            ->assertSuccessful();

        $this->assertFileExists(config_path('digipay.php'));

        if (file_exists(config_path('digipay.php'))) {
            unlink(config_path('digipay.php'));
        }
    }
}
