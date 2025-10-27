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
        protected ?string $token = null,
        protected array $filters = []
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

        // Use filters passed to constructor first, then fall back to request parameters
        $filters = !empty($this->filters) ? $this->filters : request()->all();
        
        // Add common query parameters for filtering transactions
        // Note: business_id is now part of the URL path, not a query parameter
        
        if (isset($filters['status'])) {
            $query['status'] = $filters['status'];
        }

        if (isset($filters['payment_status'])) {
            $query['payment_status'] = $filters['payment_status'];
        }

        if (isset($filters['date_from'])) {
            $query['date_from'] = $filters['date_from'];
        }

        if (isset($filters['date_to'])) {
            $query['date_to'] = $filters['date_to'];
        }

        if (isset($filters['limit'])) {
            $query['limit'] = $filters['limit'];
        }

        if (isset($filters['per_page'])) {
            $query['per_page'] = $filters['per_page'];
        }

        if (isset($filters['page'])) {
            $query['page'] = $filters['page'];
        }

        if (isset($filters['search'])) {
            $query['search'] = $filters['search'];
        }

        if (isset($filters['sort_by'])) {
            $query['sort_by'] = $filters['sort_by'];
        }

        if (isset($filters['sort_order'])) {
            $query['sort_order'] = $filters['sort_order'];
        }

        return $query;
    }
}
