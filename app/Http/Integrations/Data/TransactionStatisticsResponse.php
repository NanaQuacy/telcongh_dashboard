<?php

namespace App\Http\Integrations\Data;

use Saloon\Http\Response;

class TransactionStatisticsResponse
{
    public function __construct(
        public readonly int $totalTransactions,
        public readonly int $activeTransactions,
        public readonly int $pendingCount,
        public readonly int $inProgressCount,
        public readonly int $completedCount,
        public readonly int $cancelledCount,
        public readonly int $refundedCount,
        public readonly string $totalCost,
        public readonly string $totalRevenue,
        public readonly string $totalProfit
    ) {}

    public static function fromResponse(Response $response): self
    {
        $data = $response->json();

        // Handle different response structures
        if (isset($data['data'])) {
            $stats = $data['data'];
        } else {
            $stats = $data;
        }

        return new self(
            totalTransactions: $stats['total_transactions'] ?? 0,
            activeTransactions: $stats['active_transactions'] ?? 0,
            pendingCount: $stats['pending_count'] ?? 0,
            inProgressCount: $stats['in_progress_count'] ?? 0,
            completedCount: $stats['completed_count'] ?? 0,
            cancelledCount: $stats['cancelled_count'] ?? 0,
            refundedCount: $stats['refunded_count'] ?? 0,
            totalCost: $stats['total_cost'] ?? '0.00',
            totalRevenue: $stats['total_revenue'] ?? '0.00',
            totalProfit: $stats['total_profit'] ?? '0.00'
        );
    }

    // Getter methods for easy access
    public function getTotalTransactions(): int
    {
        return $this->totalTransactions;
    }

    public function getActiveTransactions(): int
    {
        return $this->activeTransactions;
    }

    public function getPendingCount(): int
    {
        return $this->pendingCount;
    }

    public function getInProgressCount(): int
    {
        return $this->inProgressCount;
    }

    public function getCompletedCount(): int
    {
        return $this->completedCount;
    }

    public function getCancelledCount(): int
    {
        return $this->cancelledCount;
    }

    public function getRefundedCount(): int
    {
        return $this->refundedCount;
    }

    public function getTotalCost(): string
    {
        return $this->totalCost;
    }

    public function getTotalRevenue(): string
    {
        return $this->totalRevenue;
    }

    public function getTotalProfit(): string
    {
        return $this->totalProfit;
    }

    public function isSuccessful(): bool
    {
        return true; // This DTO is only created from successful responses
    }
}
