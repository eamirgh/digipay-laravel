<?php

namespace Eamirgh\Digipay\Tests\Unit;

use Eamirgh\Digipay\DigipayClient;
use Eamirgh\Digipay\Exceptions\DigipayAuthenticationException;
use Eamirgh\Digipay\Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AuthenticationTest extends TestCase
{
    public function test_fetches_and_caches_oauth_token(): void
    {
        Http::fake([
            'https://uat.mydigipay.info/digipay/api/oauth/token' => Http::response([
                'access_token' => 'sample_access_token_123',
                'token_type' => 'bearer',
                'refresh_token' => 'sample_refresh_token',
                'expires_in' => 3600,
                'scope' => 'USER',
            ], 200),
        ]);

        /** @var DigipayClient $client */
        $client = $this->app->make(DigipayClient::class);

        $token = $client->getAccessToken();

        $this->assertSame('sample_access_token_123', $token);

        Http::assertSent(function ($request) {
            $expectedBasicAuth = 'Basic ' . base64_encode('test_client_id:test_client_secret');
            return $request->url() === 'https://uat.mydigipay.info/digipay/api/oauth/token'
                && $request->header('Authorization')[0] === $expectedBasicAuth;
        });
    }

    public function test_uses_cached_token_on_subsequent_calls(): void
    {
        Http::fake([
            'https://uat.mydigipay.info/digipay/api/oauth/token' => Http::response([
                'access_token' => 'first_token',
                'token_type' => 'bearer',
                'expires_in' => 3600,
            ], 200),
        ]);

        /** @var DigipayClient $client */
        $client = $this->app->make(DigipayClient::class);

        $token1 = $client->getAccessToken();
        $token2 = $client->getAccessToken();

        $this->assertSame('first_token', $token1);
        $this->assertSame('first_token', $token2);

        // Only 1 request sent due to caching
        Http::assertSentCount(1);
    }

    public function test_force_refresh_bypasses_cache(): void
    {
        Http::fake([
            'https://uat.mydigipay.info/digipay/api/oauth/token' => Http::sequence()
                ->push(['access_token' => 'first_token', 'expires_in' => 3600], 200)
                ->push(['access_token' => 'refreshed_token', 'expires_in' => 3600], 200),
        ]);

        /** @var DigipayClient $client */
        $client = $this->app->make(DigipayClient::class);

        $token1 = $client->getAccessToken();
        $token2 = $client->getAccessToken(forceRefresh: true);

        $this->assertSame('first_token', $token1);
        $this->assertSame('refreshed_token', $token2);
        Http::assertSentCount(2);
    }

    public function test_authentication_failure_throws_exception(): void
    {
        Http::fake([
            'https://uat.mydigipay.info/digipay/api/oauth/token' => Http::response([
                'error' => 'invalid_grant',
                'error_description' => 'Bad credentials',
            ], 401),
        ]);

        $this->expectException(DigipayAuthenticationException::class);
        $this->expectExceptionMessage('Bad credentials');

        /** @var DigipayClient $client */
        $client = $this->app->make(DigipayClient::class);
        $client->getAccessToken();
    }

    public function test_missing_credentials_throws_exception(): void
    {
        /** @var DigipayClient $client */
        $client = $this->app->make(DigipayClient::class);
        $client->setConfig([
            'client_id' => '',
            'client_secret' => '',
        ]);

        $this->expectException(DigipayAuthenticationException::class);
        $this->expectExceptionMessage('Digipay OAuth credentials are not properly configured.');

        $client->getAccessToken(forceRefresh: true);
    }
}
