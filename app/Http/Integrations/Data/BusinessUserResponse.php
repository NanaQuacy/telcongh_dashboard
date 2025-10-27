<?php

namespace App\Http\Integrations\Data;

use Saloon\Http\Response;

class BusinessUserResponse
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly int $businessId,
        public readonly bool $isActive,
        public readonly bool $isVerified,
        public readonly bool $isDeleted,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly array $user,
        public readonly array $business,
        public readonly ?array $roles = null,
    ) {}

    public static function fromResponse(Response $response): self
    {
        $data = $response->json('data', []);

        return new self(
            id: $data['id'] ?? 0,
            userId: $data['user_id'] ?? 0,
            businessId: $data['business_id'] ?? 0,
            isActive: (bool) ($data['is_active'] ?? false),
            isVerified: (bool) ($data['is_verified'] ?? false),
            isDeleted: (bool) ($data['is_deleted'] ?? false),
            createdAt: $data['created_at'] ?? '',
            updatedAt: $data['updated_at'] ?? '',
            user: $data['user'] ?? [],
            business: $data['business'] ?? [],
            roles: $data['roles'] ?? null,
        );
    }

    public static function fromArray(array $data): self
    {
        // Debug: Log the data being processed
        \Log::info('BusinessUserResponse::fromArray processing', ['data' => $data]);
        
        // Extract roles from nested user object
        $userData = $data['user'] ?? [];
        $roles = $userData['roles'] ?? null;
        
        return new self(
            id: $data['id'] ?? 0,
            userId: $data['user_id'] ?? 0,
            businessId: $data['business_id'] ?? 0,
            isActive: (bool) ($data['is_active'] ?? false),
            isVerified: (bool) ($data['is_verified'] ?? false),
            isDeleted: (bool) ($data['is_deleted'] ?? false),
            createdAt: $data['created_at'] ?? '',
            updatedAt: $data['updated_at'] ?? '',
            user: $userData,
            business: $data['business'] ?? [],
            roles: $roles,
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getBusinessId(): int
    {
        return $this->businessId;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function getUser(): array
    {
        return $this->user;
    }

    public function getBusiness(): array
    {
        return $this->business;
    }

    public function getRoles(): ?array
    {
        return $this->roles;
    }

    public function getUserName(): string
    {
        return $this->user['name'] ?? '';
    }

    public function getUserEmail(): string
    {
        return $this->user['email'] ?? '';
    }

    public function getUserPhone(): string
    {
        return $this->user['phone'] ?? '';
    }

    public function getBusinessName(): string
    {
        return $this->business['name'] ?? '';
    }

    public function getBusinessCode(): string
    {
        return $this->business['business_code'] ?? '';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'business_id' => $this->businessId,
            'is_active' => $this->isActive,
            'is_verified' => $this->isVerified,
            'is_deleted' => $this->isDeleted,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'user' => $this->user,
            'business' => $this->business,
            'roles' => $this->roles,
        ];
    }
}
