<?php

namespace App\Http\Integrations\Data;

use Illuminate\Support\Facades\Log;

class NetworkServicePricingResponse
{
    public bool $success;
    public string $message;
    public ?array $data;
    public array $errors;

    public function __construct(
        bool $success = false,
        string $message = '',
        ?array $data = null,
        array $errors = []
    ) {
        $this->success = $success;
        $this->message = $message;
        $this->data = $data;
        $this->errors = $errors;
    }

    public static function fromResponse($response): self
    {
        $status = $response->status();
        $body = $response->json();

        Log::info('Network Service Pricing API Response', [
            'status' => $status,
            'body' => $body
        ]);

        if ($response->successful() && isset($body['data'])) {
            return new self(
                success: true,
                message: $body['message'] ?? 'Network service pricing retrieved successfully',
                data: $body['data'],
                errors: []
            );
        }

        return new self(
            success: false,
            message: $body['message'] ?? 'Failed to retrieve network service pricing',
            data: null,
            errors: $body['errors'] ?? ['network_service_pricing' => 'Unable to retrieve network service pricing']
        );
    }

    public function isSuccessful(): bool
    {
        return $this->success;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getNetworkServicePricing(): ?array
    {
        return $this->data;
    }

    public function getServiceName(): ?string
    {
        return $this->data['network_service']['service']['name'] ?? null;
    }

    public function getNetworkName(): ?string
    {
        return $this->data['network_service']['network']['name'] ?? null;
    }

    public function getSellingPrice(): ?float
    {
        return isset($this->data['selling_price']) ? (float) $this->data['selling_price'] : null;
    }

    public function getCostPrice(): ?float
    {
        return isset($this->data['cost_price']) ? (float) $this->data['cost_price'] : null;
    }

    public function getProfit(): ?float
    {
        return isset($this->data['profit']) ? (float) $this->data['profit'] : null;
    }

    public function getProfitMargin(): ?int
    {
        return $this->data['profit_margin'] ?? null;
    }

    public function isActive(): bool
    {
        return $this->data['is_active'] ?? false;
    }
}
