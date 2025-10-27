<?php

namespace App\Http\Integrations\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DownloadCustomerServiceDetailsPdfRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected int $businessId,
        protected string $token
    ) {}

    public function resolveEndpoint(): string
    {
        return "/customer-service-details/by-business/{$this->businessId}/download/pdf";
    }

    protected function defaultHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/pdf',
            'Content-Type' => 'application/json',
        ];
    }
}
