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
        $data = $response->json('data', []);
        $meta = $response->json('meta', []);

        $permissions = [];
        if (is_array($data)) {
            foreach ($data as $permissionData) {
                $permissions[] = PermissionResponse::fromArray($permissionData);
            }
        }

        return new self(
            permissions: $permissions,
            total: $meta['total'] ?? count($permissions),
            perPage: $meta['per_page'] ?? 15,
            currentPage: $meta['current_page'] ?? 1,
            lastPage: $meta['last_page'] ?? 1,
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
