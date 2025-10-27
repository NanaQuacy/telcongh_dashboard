<?php

namespace App\Http\Integrations\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetCustomerServiceDetailsRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected int $businessId,
        protected string $token
    ) {}

    public function resolveEndpoint(): string
    {
        return "/customer-service-details/by-business/{$this->businessId}";
    }

    protected function defaultHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }
}
