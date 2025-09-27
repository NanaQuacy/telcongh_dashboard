<?php

namespace App\Http\Integrations\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class NetworkRequest extends Request
{

    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/networks';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    protected function defaultQuery(): array
    {
        return [
            'page' => $this->page ?? 1,
            'per_page' => $this->perPage ?? 15,
        ];
    }

    public function __construct(
        public string $token,
        public ?int $page = null,
        public ?int $perPage = null
    ) {
        //
    }
}
