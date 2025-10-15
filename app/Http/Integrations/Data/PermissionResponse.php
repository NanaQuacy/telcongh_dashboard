<?php

namespace App\Http\Integrations\Data;

use Saloon\Http\Response;

class PermissionResponse
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $description,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?array $roles = null,
        public readonly ?array $users = null,
    ) {}

    public static function fromResponse(Response $response): self
    {
        $data = $response->json('data', []);

        return new self(
            id: $data['id'] ?? 0,
            name: $data['name'] ?? '',
            description: $data['description'] ?? '',
            createdAt: $data['created_at'] ?? '',
            updatedAt: $data['updated_at'] ?? '',
            roles: $data['roles'] ?? null,
            users: $data['users'] ?? null,
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? 0,
            name: $data['name'] ?? '',
            description: $data['description'] ?? '',
            createdAt: $data['created_at'] ?? '',
            updatedAt: $data['updated_at'] ?? '',
            roles: $data['roles'] ?? null,
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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function getRoles(): ?array
    {
        return $this->roles;
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
            'description' => $this->description,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'roles' => $this->roles,
            'users' => $this->users,
        ];
    }
}
