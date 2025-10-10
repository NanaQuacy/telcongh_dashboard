<?php

namespace App\Http\Integrations\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetBusinessTransactionsRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected int $businessId,
        protected ?string $token = null
    ) {}

    public function resolveEndpoint(): string
    {
        return "/transactions/business/{$this->businessId}";
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

        // Add query parameters for filtering business transactions
        
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

        if (request()->has('network_id')) {
            $query['network_id'] = request()->get('network_id');
        }

        if (request()->has('service_id')) {
            $query['service_id'] = request()->get('service_id');
        }

        return $query;
    }
}
