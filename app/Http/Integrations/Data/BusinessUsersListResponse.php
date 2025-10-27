<?php

namespace App\Http\Integrations\Data;

use Saloon\Http\Response;

class BusinessUsersListResponse
{
    public function __construct(
        public readonly array $users,
        public readonly int $total,
        public readonly int $perPage,
        public readonly int $currentPage,
        public readonly int $lastPage,
        public readonly int $from,
        public readonly int $to,
        public readonly ?string $nextPageUrl = null,
        public readonly ?string $prevPageUrl = null,
    ) {}

    public static function fromResponse(Response $response): self
    {
        // Debug: Log the raw response
        \Log::info('Raw Business Users API response', ['response' => $response->json()]);
        
        // The API response structure is: { "status": "success", "data": { "data": [...] } }
        $responseData = $response->json('data', []);
        $data = $responseData['data'] ?? $responseData; // Handle nested data structure

        $users = [];
        if (is_array($data)) {
            foreach ($data as $userData) {
                \Log::info('Processing business user data', ['userData' => $userData]);
                $users[] = BusinessUserResponse::fromArray($userData);
            }
        }

        return new self(
            users: $users,
            total: $responseData['total'] ?? count($users),
            perPage: $responseData['per_page'] ?? 15,
            currentPage: $responseData['current_page'] ?? 1,
            lastPage: $responseData['last_page'] ?? 1,
            from: $responseData['from'] ?? 1,
            to: $responseData['to'] ?? count($users),
            nextPageUrl: $responseData['next_page_url'] ?? null,
            prevPageUrl: $responseData['prev_page_url'] ?? null,
        );
    }

    public function getUsers(): array
    {
        return $this->users;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getLastPage(): int
    {
        return $this->lastPage;
    }

    public function getFrom(): int
    {
        return $this->from;
    }

    public function getTo(): int
    {
        return $this->to;
    }

    public function getNextPageUrl(): ?string
    {
        return $this->nextPageUrl;
    }

    public function getPrevPageUrl(): ?string
    {
        return $this->prevPageUrl;
    }

    public function toArray(): array
    {
        return [
            'users' => array_map(fn($user) => $user->toArray(), $this->users),
            'total' => $this->total,
            'per_page' => $this->perPage,
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage,
            'from' => $this->from,
            'to' => $this->to,
            'next_page_url' => $this->nextPageUrl,
            'prev_page_url' => $this->prevPageUrl,
        ];
    }
}
