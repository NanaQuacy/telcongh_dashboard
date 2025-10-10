<?php

namespace App\Http\Integrations\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetTransactionRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected ?int $businessId = null,
        protected ?int $transactionId = null,
        protected ?string $token = null
    ) {}

    public function resolveEndpoint(): string
    {
        if ($this->transactionId) {
            return "/transactions/{$this->transactionId}";
        }
        
        if ($this->businessId) {
            return "/transactions/business/{$this->businessId}";
        }
        
        return '/transactions';
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
        $query = [];

        // Add common query parameters for filtering transactions
        // Note: business_id is now part of the URL path, not a query parameter
        
        if (request()->has('status')) {
            $query['status'] = request()->get('status');
        }

        if (request()->has('payment_status')) {
            $query['payment_status'] = request()->get('payment_status');
        }

        if (request()->has('date_from')) {
            $query['date_from'] = request()->get('date_from');
        }

        if (request()->has('date_to')) {
            $query['date_to'] = request()->get('date_to');
        }

        if (request()->has('limit')) {
            $query['limit'] = request()->get('limit');
        }

        if (request()->has('page')) {
            $query['page'] = request()->get('page');
        }

        if (request()->has('search')) {
            $query['search'] = request()->get('search');
        }

        if (request()->has('sort_by')) {
            $query['sort_by'] = request()->get('sort_by');
        }

        if (request()->has('sort_order')) {
            $query['sort_order'] = request()->get('sort_order');
        }

        return $query;
    }
}
