<?php

namespace App\Http\Integrations\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Contracts\Body\HasBody;
use Saloon\Traits\Body\HasJsonBody;

class PaymentDetailsRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected array $data,
        protected string $token
    ) {}

    public function resolveEndpoint(): string
    {
        return '/payment-details';
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
            'transaction_id' => $this->data['transaction_id'],
            'payment_method' => $this->data['payment_method'],
            'payment_amount' => $this->data['payment_amount'],
            'paid_amount' => $this->data['paid_amount'],
            'payment_status' => $this->data['payment_status'],
            'business_id' => $this->data['business_id'],
        ];

        // Add optional fields if they exist
        if (isset($this->data['due_amount'])) {
            $body['due_amount'] = $this->data['due_amount'];
        }

        if (isset($this->data['payment_date'])) {
            $body['payment_date'] = $this->data['payment_date'];
        }

        if (isset($this->data['payment_notes'])) {
            $body['payment_notes'] = $this->data['payment_notes'];
        }

        return $body;
    }
}
