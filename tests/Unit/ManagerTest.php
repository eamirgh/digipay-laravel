<?php

namespace Eamirgh\Digipay\Tests\Unit;

use Eamirgh\Digipay\DigipayClient;
use Eamirgh\Digipay\DigipayManager;
use Eamirgh\Digipay\Facades\Digipay;
use Eamirgh\Digipay\Tests\TestCase;

class ManagerTest extends TestCase
{
    public function test_manager_resolves_default_connection(): void
    {
        $manager = $this->app->make(DigipayManager::class);

        $client = $manager->connection();
        $this->assertInstanceOf(DigipayClient::class, $client);
        $this->assertSame('test_client_id', $client->getConfig('client_id'));

        // Calling same connection returns same instance (cached)
        $this->assertSame($client, $manager->connection());
    }

    public function test_manager_resolves_named_connection(): void
    {
        config()->set('digipay.connections.store_2', [
            'sandbox' => false,
            'client_id' => 'store_2_client_id',
            'client_secret' => 'store_2_secret',
            'username' => 'store_2_user',
            'password' => 'store_2_pass',
        ]);

        $manager = $this->app->make(DigipayManager::class);
        $store2Client = $manager->connection('store_2');

        $this->assertInstanceOf(DigipayClient::class, $store2Client);
        $this->assertSame('store_2_client_id', $store2Client->getConfig('client_id'));
        $this->assertFalse($store2Client->isSandbox());
        $this->assertSame('https://api.mydigipay.com/digipay/api', $store2Client->getBaseUrl());
    }

    public function test_manager_builds_dynamic_client(): void
    {
        $manager = $this->app->make(DigipayManager::class);

        $dynamicClient = $manager->build([
            'sandbox' => true,
            'client_id' => 'dynamic_client_id',
            'client_secret' => 'dynamic_secret',
            'username' => 'dynamic_user',
            'password' => 'dynamic_pass',
        ]);

        $this->assertInstanceOf(DigipayClient::class, $dynamicClient);
        $this->assertSame('dynamic_client_id', $dynamicClient->getConfig('client_id'));
    }

    public function test_facade_forwards_calls_to_default_connection_and_supports_connection_switch(): void
    {
        config()->set('digipay.connections.custom', [
            'client_id' => 'custom_id',
            'client_secret' => 'custom_sec',
            'username' => 'custom_usr',
            'password' => 'custom_pwd',
        ]);

        $this->assertSame('test_client_id', Digipay::getConfig('client_id'));
        $this->assertSame('custom_id', Digipay::connection('custom')->getConfig('client_id'));
    }
}
