<?php

namespace App\Http\Integrations;

use Saloon\Http\Connector;

class TelconApiConnector extends Connector
{

    /**
     * The base URL of the API
     */
    public function resolveBaseUrl(): string
    {
        $baseUrl = config('services.telcon.base_url', env('TELCON_API_BASE_URL', 'https://api.telcon.com/v1'));
        return rtrim($baseUrl, '/');
    }

    /**
     * Default headers for every request
     */
    protected function defaultHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
        ];

        // Add API key if configured
        if ($apiKey = config('services.telcon.api_key')) {
            $headers['Authorization'] = 'Bearer ' . $apiKey;
        }

        return $headers;
    }

    /**
     * Default HTTP client options
     */
    protected function defaultConfig(): array
    {
        return [
            'timeout' => config('services.telcon.timeout', 30),
        ];
    }
}
