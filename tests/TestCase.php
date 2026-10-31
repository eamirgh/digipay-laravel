<?php

namespace Eamirgh\Digipay\Tests;

use Eamirgh\Digipay\DigipayServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            DigipayServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('digipay.sandbox', true);
        $app['config']->set('digipay.client_id', 'test_client_id');
        $app['config']->set('digipay.client_secret', 'test_client_secret');
        $app['config']->set('digipay.username', 'test_username');
        $app['config']->set('digipay.password', 'test_password');
        $app['config']->set('digipay.default_type', 11);
        $app['config']->set('digipay.callback_url', 'https://example.com/callback');
    }
}
