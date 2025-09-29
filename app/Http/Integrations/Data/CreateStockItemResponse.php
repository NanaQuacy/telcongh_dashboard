<?php

namespace App\Http\Integrations\Data;

use Saloon\Http\Response;

class CreateStockItemResponse
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
            message: $data['message'] ?? ($response->successful() ? 'Success' : 'Failed to create stock items'),
            data: $data['data'] ?? null,
            errors: $data['errors'] ?? []
        );
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function getCreatedItems(): array
    {
        return $this->data['items'] ?? [];
    }

    public function getItemsCount(): int
    {
        return count($this->getCreatedItems());
    }

    public function getStockBatchId(): ?int
    {
        return $this->data['stock_batch_id'] ?? null;
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
