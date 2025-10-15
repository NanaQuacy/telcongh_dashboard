<?php

namespace App\Http\Integrations\Data;

use Saloon\Http\Response;

class UsersListResponse
{
    public function __construct(
        public readonly array $users,
        public readonly int $total,
        public readonly int $perPage,
        public readonly int $currentPage,
        public readonly int $lastPage,
    ) {}

    public static function fromResponse(Response $response): self
    {
        $data = $response->json('data', []);
        $meta = $response->json('meta', []);

        return new self(
            users: $data ?? [],
            total: $meta['total'] ?? count($data ?? []),
            perPage: $meta['per_page'] ?? 15,
            currentPage: $meta['current_page'] ?? 1,
            lastPage: $meta['last_page'] ?? 1,
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

    public function toArray(): array
    {
        return [
            'users' => $this->users,
            'total' => $this->total,
            'per_page' => $this->perPage,
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage,
        ];
    }
}
