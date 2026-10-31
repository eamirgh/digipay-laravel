<?php

namespace Eamirgh\Digipay;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use InvalidArgumentException;

/**
 * @mixin \Eamirgh\Digipay\DigipayClient
 */
class DigipayManager
{
    /**
     * The active client instances.
     *
     * @var array<string, \Eamirgh\Digipay\DigipayClient>
     */
    protected array $clients = [];

    public function __construct(
        protected Application $app
    ) {
    }

    public function getDefaultConnection(): string
    {
        return $this->app['config']->get('digipay.default', 'default');
    }

    public function connection(?string $name = null): DigipayClient
    {
        $name = $name ?: $this->getDefaultConnection();

        if (!isset($this->clients[$name])) {
            $this->clients[$name] = $this->resolve($name);
        }

        return $this->clients[$name];
    }

    public function build(array $config): DigipayClient
    {
        $rootConfig = $this->app['config']->get('digipay', []);
        $mergedConfig = array_replace_recursive($rootConfig, $config);

        $cacheStoreName = $mergedConfig['cache']['store'] ?? null;
        $cache = $this->app->make(CacheFactory::class)->store($cacheStoreName);
        $http = $this->app->make(HttpFactory::class);

        return new DigipayClient($mergedConfig, $cache, $http);
    }

    protected function resolve(string $name): DigipayClient
    {
        $config = $this->configuration($name);

        if (empty($config)) {
            throw new InvalidArgumentException("Digipay connection [{$name}] not configured.");
        }

        $cacheStoreName = $config['cache']['store'] ?? null;
        $cache = $this->app->make(CacheFactory::class)->store($cacheStoreName);
        $http = $this->app->make(HttpFactory::class);

        return new DigipayClient($config, $cache, $http);
    }

    protected function configuration(string $name): array
    {
        $rootConfig = $this->app['config']->get('digipay', []);

        if ($name === 'default' && !isset($rootConfig['connections']['default'])) {
            return $rootConfig;
        }

        $connectionConfig = $rootConfig['connections'][$name] ?? null;

        if ($connectionConfig === null) {
            if ($name === 'default') {
                return $rootConfig;
            }
            return [];
        }

        return array_replace_recursive($rootConfig, $connectionConfig);
    }

    public function __call(string $method, array $parameters): mixed
    {
        return $this->connection()->$method(...$parameters);
    }
}
