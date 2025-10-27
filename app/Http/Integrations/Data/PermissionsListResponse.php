<?php

namespace App\Http\Integrations\Data;

use Saloon\Http\Response;

class PermissionsListResponse
{
    public function __construct(
        public readonly array $permissions,
        public readonly int $total,
        public readonly int $perPage,
        public readonly int $currentPage,
        public readonly int $lastPage,
    ) {}

    public static function fromResponse(Response $response): self
    {
        // Debug: Log the raw response
        \Log::info('Raw Permissions API response', ['response' => $response->json()]);
        
        // The API response structure is: { "status": "success", "data": { "data": [...] } }
        $responseData = $response->json('data', []);
        $data = $responseData['data'] ?? $responseData; // Handle nested data structure
        $pagination = $responseData['pagination'] ?? $response->json('pagination', []);

        $permissions = [];
        if (is_array($data)) {
            foreach ($data as $permissionData) {
                \Log::info('Processing permission data', ['permissionData' => $permissionData]);
                $permissions[] = PermissionResponse::fromArray($permissionData);
            }
        }

        return new self(
            permissions: $permissions,
            total: $pagination['total'] ?? count($permissions),
            perPage: $pagination['per_page'] ?? 15,
            currentPage: $pagination['current_page'] ?? 1,
            lastPage: $pagination['last_page'] ?? 1,
        );
    }

    public function getPermissions(): array
    {
        return $this->permissions;
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
            'permissions' => array_map(fn($permission) => $permission->toArray(), $this->permissions),
            'total' => $this->total,
            'per_page' => $this->perPage,
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage,
        ];
    }
}
