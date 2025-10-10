<?php

namespace App\Http\Integrations\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Contracts\Body\HasBody;
use Saloon\Traits\Body\HasJsonBody;

class TransactionRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected array $data,
        protected string $token
    ) {}

    public function resolveEndpoint(): string
    {
        return '/transactions';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    protected function defaultBody(): array
    {
        $body = [
            'network_service_id' => $this->data['network_service_id'],
            'business_id' => $this->data['business_id'],
            'customer_service_details_id' => $this->data['customer_service_details_id'], // Required field
            'network_id' => $this->data['network_id'],
            'cost_price' => $this->data['cost_price'],
            'selling_price' => $this->data['selling_price'],
            'transaction_status' => $this->data['transaction_status'],
        ];

        // Optional fields
        if (isset($this->data['service_id'])) {
            $body['service_id'] = $this->data['service_id'];
        }

        if (isset($this->data['profit'])) {
            $body['profit'] = $this->data['profit'];
        }

        if (isset($this->data['transaction_notes'])) {
            $body['transaction_notes'] = $this->data['transaction_notes'];
        }

        if (isset($this->data['is_active'])) {
            $body['is_active'] = $this->data['is_active'];
        }

        if (isset($this->data['is_deleted'])) {
            $body['is_deleted'] = $this->data['is_deleted'];
        }

        \Log::info('Transaction request body created', [
            'transaction_data' => $body,
            'required_fields_present' => [
                'network_service_id' => isset($body['network_service_id']),
                'business_id' => isset($body['business_id']),
                'customer_service_details_id' => isset($body['customer_service_details_id']),
                'network_id' => isset($body['network_id']),
                'cost_price' => isset($body['cost_price']),
                'selling_price' => isset($body['selling_price']),
                'transaction_status' => isset($body['transaction_status']),
            ]
        ]);

        return $body;
    }
}
