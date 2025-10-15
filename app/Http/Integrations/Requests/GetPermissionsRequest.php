<?php

namespace App\Http\Integrations\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetPermissionsRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected ?string $token = null
    ) {}

    public function resolveEndpoint(): string
    {
        return "/permissions";
    }

    protected function defaultHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
        ];

        if ($this->token) {
            $headers['Authorization'] = 'Bearer ' . $this->token;
        }

        return $headers;
    }

    protected function defaultQuery(): array
    {
        return [];
    }
}
