<?php

namespace App\Http\Integrations\Data;

use Saloon\Http\Response;

class CreateStockBatchResponse
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
            message: $data['message'] ?? ($response->successful() ? 'Success' : 'Failed to create stock batch'),
            data: $data['data'] ?? null,
            errors: $data['errors'] ?? []
        );
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function getBatchId(): ?int
    {
        return $this->data['id'] ?? null;
    }

    public function getBatchName(): ?string
    {
        return $this->data['name'] ?? null;
    }

    public function getQuantity(): ?int
    {
        return $this->data['quantity'] ?? null;
    }

    public function getCost(): ?float
    {
        return $this->data['cost'] ?? null;
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
