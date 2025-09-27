<?php

namespace App\Http\Integrations\Data;

use Saloon\Http\Response;

class NetworkResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly array $networks,
        public readonly array $pagination,
        public readonly array $errors
    ) {}

    public static function fromResponse(Response $response): self
    {
        try {
            $data = $response->json();
        } catch (\Exception $e) {
            return new self(
                success: false,
                message: 'Invalid JSON response from API',
                networks: [],
                pagination: [],
                errors: ['json' => 'Failed to parse API response: ' . $e->getMessage()]
            );
        }

        // Check if response is successful AND has data
        if ($response->successful() && isset($data['data'])) {
            return new self(
                success: true,
                message: $data['message'] ?? 'Networks retrieved successfully',
                networks: $data['data'] ?? $data['networks'] ?? [],
                pagination: $data['pagination'] ?? $data['meta'] ?? [],
                errors: []
            );
        }

        return new self(
            success: false,
            message: $data['message'] ?? 'Failed to retrieve networks',
            networks: [],
            pagination: [],
            errors: $data['errors'] ?? ['network' => 'Failed to retrieve networks']
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

    public function getNetworks(): array
    {
        return $this->networks;
    }

    public function getPagination(): array
    {
        return $this->pagination;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getNetworkCount(): int
    {
        return count($this->networks);
    }

    public function hasNetworks(): bool
    {
        return !empty($this->networks);
    }

    public function getTotalPages(): int
    {
        return $this->pagination['last_page'] ?? $this->pagination['total_pages'] ?? 1;
    }

    public function getCurrentPage(): int
    {
        return $this->pagination['current_page'] ?? 1;
    }

    public function getTotalCount(): int
    {
        return $this->pagination['total'] ?? 0;
    }
}
