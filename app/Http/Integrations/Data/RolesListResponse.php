<?php

namespace App\Http\Integrations\Data;

use Saloon\Http\Response;

class RolesListResponse
{
    public function __construct(
        public readonly array $roles,
        public readonly int $total,
        public readonly int $perPage,
        public readonly int $currentPage,
        public readonly int $lastPage,
    ) {}

    public static function fromResponse(Response $response): self
    {
        // Debug: Log the raw response
        \Log::info('Raw API response', ['response' => $response->json()]);
        
        // The API response structure is: { "status": "success", "data": { "data": [...] } }
        $responseData = $response->json('data', []);
        $data = $responseData['data'] ?? $responseData; // Handle nested data structure
        $pagination = $responseData['pagination'] ?? $response->json('pagination', []);

        $roles = [];
        if (is_array($data)) {
            foreach ($data as $roleData) {
                \Log::info('Processing role data', ['roleData' => $roleData]);
                $roles[] = RoleResponse::fromArray($roleData);
            }
        }

        return new self(
            roles: $roles,
            total: $pagination['total'] ?? count($roles),
            perPage: $pagination['per_page'] ?? 15,
            currentPage: $pagination['current_page'] ?? 1,
            lastPage: $pagination['last_page'] ?? 1,
        );
    }

    public function getRoles(): array
    {
        return $this->roles;
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
            'roles' => array_map(fn($role) => $role->toArray(), $this->roles),
            'total' => $this->total,
            'per_page' => $this->perPage,
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage,
        ];
    }
}