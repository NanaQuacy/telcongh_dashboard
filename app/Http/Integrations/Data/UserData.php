<?php

namespace App\Http\Integrations\Data;

class UserData
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $avatar = null,
        public readonly ?string $phone = null,
        public readonly ?string $role = null,
        public readonly ?array $permissions = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {
        //
    }

    /**
     * Create UserData from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            email: $data['email'],
            avatar: $data['avatar'] ?? null,
            phone: $data['phone'] ?? null,
            role: $data['role'] ?? null,
            permissions: $data['permissions'] ?? null,
            createdAt: $data['created_at'] ?? $data['createdAt'] ?? null,
            updatedAt: $data['updated_at'] ?? $data['updatedAt'] ?? null
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'phone' => $this->phone,
            'role' => $this->role,
            'permissions' => $this->permissions,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    /**
     * Get user's full name
     */
    public function getFullName(): string
    {
        return $this->name;
    }

    /**
     * Get user's initials
     */
    public function getInitials(): string
    {
        $names = explode(' ', $this->name);
        $initials = '';
        
        foreach ($names as $name) {
            if (!empty($name)) {
                $initials .= strtoupper(substr($name, 0, 1));
            }
        }
        
        return $initials;
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }
}
