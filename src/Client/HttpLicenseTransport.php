<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Client;

use Illuminate\Http\Client\Factory;
use Kurt\Modules\Licensing\Client\Contracts\LicenseTransport;
use Kurt\Modules\Licensing\Client\Data\ValidationResponse;

/**
 * Default transport: speaks the licensing HTTP API. Built on illuminate/http
 * (not Core), so a premium package can embed it with just illuminate/* present.
 */
final class HttpLicenseTransport implements LicenseTransport
{
    /**
     * @param  array<string, string>  $headers  e.g. an API key header for the server.
     */
    public function __construct(
        private readonly Factory $http,
        private readonly string $baseUrl,
        private readonly array $headers = [],
    ) {}

    public function validate(string $key, ?string $fingerprint = null): ValidationResponse
    {
        return ValidationResponse::fromArray($this->post('validate', $this->payload($key, $fingerprint)));
    }

    public function activate(string $key, string $fingerprint, ?string $label = null): ValidationResponse
    {
        $payload = $this->payload($key, $fingerprint);

        if ($label !== null) {
            $payload['label'] = $label;
        }

        return ValidationResponse::fromArray($this->post('activate', $payload));
    }

    public function deactivate(string $key, string $fingerprint): bool
    {
        $data = $this->post('deactivate', $this->payload($key, $fingerprint));

        return (bool) ($data['deactivated'] ?? false);
    }

    /**
     * @return array<string, string>
     */
    private function payload(string $key, ?string $fingerprint): array
    {
        $payload = ['key' => $key];

        if ($fingerprint !== null) {
            $payload['fingerprint'] = $fingerprint;
        }

        return $payload;
    }

    /**
     * @param  array<string, string>  $payload
     * @return array<array-key, mixed>
     */
    private function post(string $path, array $payload): array
    {
        $json = $this->http
            ->baseUrl($this->baseUrl)
            ->withHeaders($this->headers)
            ->acceptJson()
            ->asJson()
            ->post($path, $payload)
            ->json();

        return is_array($json) ? $json : [];
    }
}
