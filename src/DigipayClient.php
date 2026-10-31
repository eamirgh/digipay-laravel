<?php

namespace Eamirgh\Digipay;

use Eamirgh\Digipay\Contracts\Payable;
use Eamirgh\Digipay\DTO\CallbackResult;
use Eamirgh\Digipay\DTO\DeliverRequest;
use Eamirgh\Digipay\DTO\DeliverResponse;
use Eamirgh\Digipay\DTO\RefundRequest;
use Eamirgh\Digipay\DTO\RefundResponse;
use Eamirgh\Digipay\DTO\ReverseRequest;
use Eamirgh\Digipay\DTO\ReverseResponse;
use Eamirgh\Digipay\DTO\TicketRequest;
use Eamirgh\Digipay\DTO\TicketResponse;
use Eamirgh\Digipay\DTO\TrackRefundResponse;
use Eamirgh\Digipay\DTO\VerifyRequest;
use Eamirgh\Digipay\DTO\VerifyResponse;
use Eamirgh\Digipay\Enums\TicketType;
use Eamirgh\Digipay\Events\PaymentRefunded;
use Eamirgh\Digipay\Events\PaymentReversed;
use Eamirgh\Digipay\Events\PaymentVerified;
use Eamirgh\Digipay\Events\TicketCreated;
use Eamirgh\Digipay\Exceptions\DigipayApiException;
use Eamirgh\Digipay\Exceptions\DigipayAuthenticationException;
use Eamirgh\Digipay\Exceptions\DigipayValidationException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DigipayClient
{
    public function __construct(
        protected array $config = [],
        protected ?CacheRepository $cache = null,
        protected ?HttpFactory $http = null
    ) {
    }

    public function getConfig(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->config;
        }

        return data_get($this->config, $key, $default);
    }

    public function setConfig(array $config): static
    {
        $this->config = array_replace_recursive($this->config, $config);
        return $this;
    }

    public function isSandbox(): bool
    {
        return (bool) ($this->config['sandbox'] ?? true);
    }

    public function getBaseUrl(): string
    {
        if (!empty($this->config['base_url'])) {
            return rtrim($this->config['base_url'], '/');
        }

        return $this->isSandbox()
            ? rtrim($this->config['staging_base_url'] ?? 'https://uat.mydigipay.info/digipay/api', '/')
            : rtrim($this->config['production_base_url'] ?? 'https://api.mydigipay.com/digipay/api', '/');
    }

    public function getAccessToken(bool $forceRefresh = false): string
    {
        $clientId = (string) ($this->config['client_id'] ?? '');
        $clientSecret = (string) ($this->config['client_secret'] ?? '');
        $username = (string) ($this->config['username'] ?? '');
        $password = (string) ($this->config['password'] ?? '');

        if ($clientId === '' || $clientSecret === '' || $username === '' || $password === '') {
            throw new DigipayAuthenticationException('Digipay OAuth credentials are not properly configured.');
        }

        $cacheKey = $this->getCacheKey($clientId, $username);

        if (!$forceRefresh && $this->cache && $this->cache->has($cacheKey)) {
            $cached = $this->cache->get($cacheKey);
            if (!empty($cached) && is_string($cached)) {
                return $cached;
            }
        }

        $url = $this->getBaseUrl() . '/oauth/token';

        $httpClient = $this->createHttpClient()
            ->withBasicAuth($clientId, $clientSecret)
            ->asMultipart();

        try {
            $response = $httpClient->post($url, [
                [
                    'name' => 'username',
                    'contents' => $username,
                ],
                [
                    'name' => 'password',
                    'contents' => $password,
                ],
                [
                    'name' => 'grant_type',
                    'contents' => 'password',
                ],
            ]);
        } catch (RequestException $e) {
            $response = $e->response;
        }

        if (!$response->successful()) {
            $body = $response->json() ?? [];
            $message = $body['error_description'] ?? $body['message'] ?? $body['error'] ?? 'Authentication with Digipay failed.';
            throw new DigipayAuthenticationException($message, $response->status(), $body);
        }

        $data = $response->json();
        $token = (string) ($data['access_token'] ?? '');

        if ($token === '') {
            throw new DigipayAuthenticationException('Digipay response did not contain an access_token.');
        }

        $expiresIn = (int) ($data['expires_in'] ?? 3600);
        $ttlBuffer = (int) ($this->config['cache']['ttl_buffer'] ?? 60);
        $ttl = max(60, $expiresIn - $ttlBuffer);

        if ($this->cache) {
            $this->cache->put($cacheKey, $token, $ttl);
        }

        return $token;
    }

    public function pay(Payable $payable, TicketType|int|null $type = null): TicketResponse
    {
        $dto = TicketRequest::fromPayable($payable, $this->config['callback_url'] ?? null);
        $resolvedType = $type ?? $payable->getDigipayType();

        return $this->createTicket($dto, $resolvedType, $payable);
    }

    public function createTicket(
        TicketRequest|array $request,
        TicketType|int|null $type = null,
        ?Payable $payable = null
    ): TicketResponse {
        $dto = $request instanceof TicketRequest
            ? $request
            : TicketRequest::fromArray($request, $this->config['callback_url'] ?? null);

        if ($dto->amount <= 0) {
            throw new DigipayValidationException('The amount field is required and must be greater than zero.', 422, [
                'amount' => ['The amount must be greater than zero.'],
            ]);
        }

        if (trim($dto->providerId) === '') {
            throw new DigipayValidationException('The providerId field is required.', 422, [
                'providerId' => ['The providerId field is required.'],
            ]);
        }

        $ticketType = $this->resolveType($type ?? $dto->type);
        $path = '/tickets/business?type=' . $ticketType;

        $response = $this->sendAuthorizedRequest('post', $path, $dto->toArray());

        $ticketResponse = TicketResponse::fromArray($response, $dto->providerId, $dto->amount);

        TicketCreated::dispatch($dto, $ticketResponse, $payable);

        return $ticketResponse;
    }

    public function parseCallback(Request|array|null $request = null): CallbackResult
    {
        if ($request === null) {
            $data = request()->all();
        } elseif ($request instanceof Request) {
            $data = $request->all();
        } else {
            $data = $request;
        }

        return CallbackResult::fromArray($data);
    }

    public function verifyPayable(
        Payable $payable,
        ?string $trackingCode = null,
        TicketType|int|null $type = null
    ): VerifyResponse {
        $resolvedTrackingCode = $trackingCode
            ?? request()->input('trackingCode')
            ?? request()->input('tracking_code')
            ?? '';

        return $this->verify(
            trackingCodeOrRequest: (string) $resolvedTrackingCode,
            providerId: $payable->getDigipayProviderId(),
            type: $type ?? $payable->getDigipayType(),
            payable: $payable
        );
    }

    public function verify(
        string|VerifyRequest|array $trackingCodeOrRequest,
        ?string $providerId = null,
        TicketType|int|null $type = null,
        ?Payable $payable = null
    ): VerifyResponse {
        if ($trackingCodeOrRequest instanceof VerifyRequest) {
            $trackingCode = $trackingCodeOrRequest->trackingCode;
            $resolvedProviderId = $trackingCodeOrRequest->providerId;
            $type = $type ?? $trackingCodeOrRequest->type;
        } elseif (is_array($trackingCodeOrRequest)) {
            $dto = VerifyRequest::fromArray($trackingCodeOrRequest);
            $trackingCode = $dto->trackingCode;
            $resolvedProviderId = $dto->providerId;
            $type = $type ?? $dto->type;
        } else {
            $trackingCode = (string) $trackingCodeOrRequest;
            $resolvedProviderId = (string) ($providerId ?? '');
        }

        if (trim($trackingCode) === '' || trim($resolvedProviderId) === '') {
            throw new DigipayValidationException('Both trackingCode and providerId are required for verification.', 422, [
                'trackingCode' => ['trackingCode is required.'],
                'providerId' => ['providerId is required.'],
            ]);
        }

        $ticketType = $this->resolveType($type);
        $path = '/purchases/verify?type=' . $ticketType;

        $response = $this->sendAuthorizedRequest('post', $path, [
            'trackingCode' => $trackingCode,
            'providerId' => $resolvedProviderId,
        ]);

        $verifyResponse = VerifyResponse::fromArray($response);

        PaymentVerified::dispatch($verifyResponse, $payable);

        return $verifyResponse;
    }

    public function reverse(
        string|ReverseRequest|array $purchaseTrackingCodeOrRequest,
        ?string $providerId = null,
        TicketType|int|null $type = null
    ): ReverseResponse {
        if ($purchaseTrackingCodeOrRequest instanceof ReverseRequest) {
            $purchaseTrackingCode = $purchaseTrackingCodeOrRequest->purchaseTrackingCode;
            $resolvedProviderId = $purchaseTrackingCodeOrRequest->providerId;
            $type = $type ?? $purchaseTrackingCodeOrRequest->type;
        } elseif (is_array($purchaseTrackingCodeOrRequest)) {
            $dto = ReverseRequest::fromArray($purchaseTrackingCodeOrRequest);
            $purchaseTrackingCode = $dto->purchaseTrackingCode;
            $resolvedProviderId = $dto->providerId;
            $type = $type ?? $dto->type;
        } else {
            $purchaseTrackingCode = (string) $purchaseTrackingCodeOrRequest;
            $resolvedProviderId = (string) ($providerId ?? '');
        }

        if (trim($purchaseTrackingCode) === '' || trim($resolvedProviderId) === '') {
            throw new DigipayValidationException('Both purchaseTrackingCode and providerId are required for reversal.', 422, [
                'purchaseTrackingCode' => ['purchaseTrackingCode is required.'],
                'providerId' => ['providerId is required.'],
            ]);
        }

        $ticketType = $this->resolveType($type);
        $path = '/reverse?type=' . $ticketType;

        $response = $this->sendAuthorizedRequest('post', $path, [
            'purchaseTrackingCode' => $purchaseTrackingCode,
            'providerId' => $resolvedProviderId,
        ]);

        $reverseResponse = ReverseResponse::fromArray($response);

        PaymentReversed::dispatch($reverseResponse);

        return $reverseResponse;
    }

    public function deliver(
        string|DeliverRequest|array $trackingCodeOrRequest,
        ?int $deliveryDate = null,
        ?string $invoiceNumber = null,
        array $products = [],
        TicketType|int|null $type = null
    ): DeliverResponse {
        if ($trackingCodeOrRequest instanceof DeliverRequest) {
            $dto = $trackingCodeOrRequest;
            $type = $type ?? $dto->type;
        } elseif (is_array($trackingCodeOrRequest)) {
            $dto = DeliverRequest::fromArray($trackingCodeOrRequest);
            $type = $type ?? $dto->type;
        } else {
            $dto = new DeliverRequest(
                trackingCode: (string) $trackingCodeOrRequest,
                deliveryDate: (int) ($deliveryDate ?? 0),
                invoiceNumber: (string) ($invoiceNumber ?? ''),
                products: $products,
                type: $type
            );
        }

        if (trim($dto->trackingCode) === '' || trim($dto->invoiceNumber) === '') {
            throw new DigipayValidationException('trackingCode and invoiceNumber are required for delivery confirmation.', 422, [
                'trackingCode' => ['trackingCode is required.'],
                'invoiceNumber' => ['invoiceNumber is required.'],
            ]);
        }

        $ticketType = $this->resolveType($type);
        $path = '/purchases/deliver?type=' . $ticketType;

        $response = $this->sendAuthorizedRequest('post', $path, $dto->toArray());

        return DeliverResponse::fromArray($response);
    }

    public function refund(
        string|RefundRequest|array $saleTrackingCodeOrRequest,
        ?int $amount = null,
        ?string $providerId = null,
        TicketType|int|null $type = null
    ): RefundResponse {
        if ($saleTrackingCodeOrRequest instanceof RefundRequest) {
            $dto = $saleTrackingCodeOrRequest;
            $type = $type ?? $dto->type;
        } elseif (is_array($saleTrackingCodeOrRequest)) {
            $dto = RefundRequest::fromArray($saleTrackingCodeOrRequest);
            $type = $type ?? $dto->type;
        } else {
            $dto = new RefundRequest(
                saleTrackingCode: (string) $saleTrackingCodeOrRequest,
                amount: (int) ($amount ?? 0),
                providerId: (string) ($providerId ?? ''),
                type: $type
            );
        }

        if ($dto->amount <= 0) {
            throw new DigipayValidationException('Refund amount must be greater than zero.', 422, [
                'amount' => ['amount must be greater than zero.'],
            ]);
        }

        if (trim($dto->saleTrackingCode) === '' || trim($dto->providerId) === '') {
            throw new DigipayValidationException('saleTrackingCode and providerId are required for refund.', 422, [
                'saleTrackingCode' => ['saleTrackingCode is required.'],
                'providerId' => ['providerId is required.'],
            ]);
        }

        $ticketType = $this->resolveType($type);
        $path = '/refunds?type=' . $ticketType;

        $response = $this->sendAuthorizedRequest('post', $path, $dto->toArray());

        $refundResponse = RefundResponse::fromArray($response, $dto->providerId, $dto->amount);

        PaymentRefunded::dispatch($refundResponse);

        return $refundResponse;
    }

    public function trackRefund(string $inquiryId, TicketType|int|null $type = null): TrackRefundResponse
    {
        $cleanId = trim($inquiryId);
        if ($cleanId === '') {
            throw new DigipayValidationException('Inquiry ID (trackingCode or providerId) is required.', 422, [
                'inquiryId' => ['Inquiry ID is required.'],
            ]);
        }

        $ticketType = $this->resolveType($type);
        $path = '/refunds/' . rawurlencode($cleanId) . '?type=' . $ticketType;

        $response = $this->sendAuthorizedRequest('post', $path, []);

        return TrackRefundResponse::fromArray($response);
    }

    protected function resolveType(TicketType|int|null $type): int
    {
        if ($type instanceof TicketType) {
            return $type->value;
        }

        if ($type !== null) {
            return (int) $type;
        }

        return (int) ($this->config['default_type'] ?? TicketType::WALLET->value);
    }

    protected function sendAuthorizedRequest(string $method, string $path, array $data = []): array
    {
        $url = $this->getBaseUrl() . '/' . ltrim($path, '/');
        $token = $this->getAccessToken();

        $client = $this->createHttpClient()
            ->withToken($token)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'Agent' => (string) ($this->config['agent'] ?? 'WEB'),
                'Digipay-Version' => (string) ($this->config['version'] ?? '2022-02-02'),
            ]);

        try {
            /** @var Response $response */
            $response = match (strtolower($method)) {
                'get' => $client->get($url, $data),
                'post' => $client->post($url, $data),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
            };
        } catch (RequestException $e) {
            $response = $e->response;
        }

        $body = $response->json() ?? [];
        $resultStatus = $body['result']['status'] ?? null;

        if (!$response->successful() || ($resultStatus !== null && $resultStatus !== 0 && $resultStatus !== '0')) {
            $message = $body['result']['message'] ?? $body['message'] ?? 'Digipay API error occurred.';
            $trackingCode = $body['trackingCode'] ?? null;
            $statusCode = $response->status();

            throw new DigipayApiException(
                message: $message,
                code: $statusCode !== 200 ? $statusCode : 400,
                digipayStatus: $resultStatus,
                trackingCode: $trackingCode,
                responseBody: $body
            );
        }

        return $body;
    }

    protected function getCacheKey(string $clientId, string $username): string
    {
        $prefix = (string) ($this->config['cache']['prefix'] ?? 'digipay_oauth_token_');
        $hash = sha1($clientId . ':' . $username . ':' . ($this->isSandbox() ? 'sandbox' : 'live'));

        return $prefix . $hash;
    }

    protected function createHttpClient(): PendingRequest
    {
        $client = $this->http
            ? $this->http->timeout((int) ($this->config['timeout'] ?? 30))
            : Http::timeout((int) ($this->config['timeout'] ?? 30));

        if (!empty($this->config['connect_timeout'])) {
            $client = $client->connectTimeout((int) $this->config['connect_timeout']);
        }

        if (!empty($this->config['retry']['times'])) {
            $client = $client->retry(
                (int) $this->config['retry']['times'],
                (int) ($this->config['retry']['sleep_milliseconds'] ?? 100)
            );
        }

        return $client;
    }
}
