<?php

namespace App\Http\Integrations\Data;

use Saloon\Http\Response;

class RoleResponse
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $guardName,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?array $permissions = null,
        public readonly ?int $permissionsCount = null,
        public readonly ?array $users = null,
    ) {}

    public static function fromResponse(Response $response): self
    {
        $data = $response->json('data', []);

        return new self(
            id: $data['id'] ?? 0,
            name: $data['name'] ?? '',
            guardName: $data['guard_name'] ?? 'web',
            createdAt: $data['created_at'] ?? '',
            updatedAt: $data['updated_at'] ?? '',
            permissions: $data['permissions'] ?? null,
            permissionsCount: $data['permissions_count'] ?? null,
            users: $data['users'] ?? null,
        );
    }

    public static function fromArray(array $data): self
    {
        // Debug: Log the data being processed
        \Log::info('RoleResponse::fromArray processing', ['data' => $data]);
        
        return new self(
            id: $data['id'] ?? 0,
            name: $data['name'] ?? '',
            guardName: $data['guard_name'] ?? 'web',
            createdAt: $data['created_at'] ?? '',
            updatedAt: $data['updated_at'] ?? '',
            permissions: $data['permissions'] ?? null,
            permissionsCount: $data['permissions_count'] ?? null,
            users: $data['users'] ?? null,
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getGuardName(): string
    {
        return $this->guardName;
    }

    public function getPermissionsCount(): ?int
    {
        return $this->permissionsCount;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function getPermissions(): ?array
    {
        return $this->permissions;
    }

    public function getUsers(): ?array
    {
        return $this->users;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'guard_name' => $this->guardName,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'permissions' => $this->permissions,
            'permissions_count' => $this->permissionsCount,
            'users' => $this->users,
        ];
    }
}
