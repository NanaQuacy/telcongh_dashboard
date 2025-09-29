<?php

namespace App\Http\Integrations\Data;

use Saloon\Http\Response;

class CreateNetworkServicePricingResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly ?array $data = null,
        public readonly array $errors = []
    ) {}

    public static function fromResponse(Response $response): self
    {
        $data = $response->json();

        return new self(
            success: $response->successful(),
            message: $data['message'] ?? ($response->successful() ? 'Success' : 'Failed to create/update network service pricing'),
            data: $data['data'] ?? null,
            errors: $data['errors'] ?? []
        );
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function getPricingId(): ?int
    {
        return $this->data['id'] ?? null;
    }

    public function getNetworkServiceId(): ?int
    {
        return $this->data['network_service_id'] ?? null;
    }

    public function getBusinessId(): ?int
    {
        return $this->data['business_id'] ?? null;
    }

    public function getCostPrice(): ?float
    {
        return $this->data['cost_price'] ?? null;
    }

    public function getSellingPrice(): ?float
    {
        return $this->data['selling_price'] ?? null;
    }

    public function isSuccessful(): bool
    {
        return $this->success;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
