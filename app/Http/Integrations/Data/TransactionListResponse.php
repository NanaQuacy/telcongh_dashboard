<?php

namespace App\Http\Integrations\Data;

use Saloon\Http\Response;

class TransactionListResponse
{
    public function __construct(
        public readonly array $transactions,
        public readonly int $currentPage,
        public readonly int $lastPage,
        public readonly int $perPage,
        public readonly int $total,
        public readonly ?string $nextPageUrl,
        public readonly ?string $prevPageUrl,
        public readonly array $links
    ) {}

    public static function fromResponse(Response $response): self
    {
        $data = $response->json();

        // Handle nested data structure
        if (isset($data['data']['data'])) {
            $transactionsData = $data['data']['data'];
            $paginationData = $data['data'];
        } elseif (isset($data['data'])) {
            $transactionsData = $data['data'];
            $paginationData = $data;
        } else {
            $transactionsData = [];
            $paginationData = [];
        }

        // Parse transactions
        $transactions = [];
        if (is_array($transactionsData)) {
            foreach ($transactionsData as $transactionData) {
                $transactions[] = TransactionItemResponse::fromArray($transactionData);
            }
        }

        return new self(
            transactions: $transactions,
            currentPage: $paginationData['current_page'] ?? 1,
            lastPage: $paginationData['last_page'] ?? 1,
            perPage: $paginationData['per_page'] ?? 15,
            total: $paginationData['total'] ?? 0,
            nextPageUrl: $paginationData['next_page_url'] ?? null,
            prevPageUrl: $paginationData['prev_page_url'] ?? null,
            links: $paginationData['links'] ?? []
        );
    }

    public function getTransactions(): array
    {
        return $this->transactions;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getLastPage(): int
    {
        return $this->lastPage;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function hasNextPage(): bool
    {
        return $this->nextPageUrl !== null;
    }

    public function hasPreviousPage(): bool
    {
        return $this->prevPageUrl !== null;
    }

    public function getNextPageUrl(): ?string
    {
        return $this->nextPageUrl;
    }

    public function getPrevPageUrl(): ?string
    {
        return $this->prevPageUrl;
    }

    public function getLinks(): array
    {
        return $this->links;
    }
}
