<?php

namespace App\Http\Integrations\Data;

use Saloon\Http\Response;

class StockBatchesResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly ?array $data = null,
        public readonly ?array $statistics = null,
        public readonly ?array $fullResponseData = null,
        public readonly array $errors = []
    ) {}

    public static function fromResponse(Response $response): self
    {
        $data = $response->json();

        return new self(
            success: $response->successful(),
            message: $data['message'] ?? ($response->successful() ? 'Success' : 'Failed to retrieve stock batches'),
            data: $data['data']['batches']['data'] ?? null,
            statistics: $data['data']['overall_statistics'] ?? null,
            fullResponseData: $data['data'] ?? null,
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

    public function getStatistics(): ?array
    {
        return $this->statistics;
    }

    public function getTotalItems(): int
    {
        return $this->statistics['total_items'] ?? 0;
    }

    public function getActiveItems(): int
    {
        return $this->statistics['active_items'] ?? 0;
    }

    public function getInactiveItems(): int
    {
        return $this->statistics['inactive_items'] ?? 0;
    }

    public function getSoldItems(): int
    {
        return $this->statistics['sold_items'] ?? 0;
    }

    public function getAvailableItems(): int
    {
        return $this->statistics['available_items'] ?? 0;
    }

    public function getUnavailableItems(): int
    {
        return $this->statistics['unavailable_items'] ?? 0;
    }

    public function getAvailabilityPercentage(): float
    {
        return $this->statistics['availability_percentage'] ?? 0.0;
    }

    /**
     * Get the full response data (including pagination, network statistics, etc.)
     */
    public function getFullData(): ?array
    {
        return $this->fullResponseData;
    }

    /**
     * Get pagination information
     */
    public function getPagination(): ?array
    {
        return $this->fullResponseData['batches'] ?? null;
    }

    /**
     * Get network statistics
     */
    public function getNetworkStatistics(): ?array
    {
        return $this->fullResponseData['network_statistics'] ?? null;
    }

    /**
     * Get total number of batches (from pagination)
     */
    public function getTotalBatches(): int
    {
        return $this->fullResponseData['batches']['total'] ?? 0;
    }

    /**
     * Get current page number
     */
    public function getCurrentPage(): int
    {
        return $this->fullResponseData['batches']['current_page'] ?? 1;
    }

    /**
     * Get last page number
     */
    public function getLastPage(): int
    {
        return $this->fullResponseData['batches']['last_page'] ?? 1;
    }
}
