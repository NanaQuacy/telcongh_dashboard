<?php

namespace App\Http\Integrations\Data;

use Saloon\Http\Response;

class StockBatchesResponse
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
            message: $data['message'] ?? ($response->successful() ? 'Success' : 'Failed to retrieve stock batches'),
            data: $data['data'] ?? null,
            errors: $data['errors'] ?? []
        );
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function getBatches(): array
    {
        return $this->data ?? [];
    }

    public function getBatchById(int $id): ?array
    {
        if (!$this->data) {
            return null;
        }

        return collect($this->data)->firstWhere('id', $id);
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
