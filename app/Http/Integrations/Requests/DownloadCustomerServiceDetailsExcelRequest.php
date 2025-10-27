<?php

namespace App\Http\Integrations\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DownloadCustomerServiceDetailsExcelRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected int $businessId,
        protected string $token
    ) {}

    public function resolveEndpoint(): string
    {
       return "/customer-service-details/by-business/{$this->businessId}/download/excel";
    }

    protected function defaultHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Type' => 'application/json',
        ];
    }
}
