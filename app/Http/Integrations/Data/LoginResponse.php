<?php

namespace App\Http\Integrations\Data;

use Saloon\Http\Response;

class LoginResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $token = null,
        public readonly ?string $refreshToken = null,
        public readonly ?UserData $user = null,
        public readonly ?array $businesses = null,
        public readonly ?string $message = null,
        public readonly ?array $errors = null,
        public readonly ?array $roles = null,
        public readonly ?array $permissions = null
    ) {
        //
    }

    /**
     * Create a login response from Saloon response
     */
    public static function fromResponse(Response $response): self
    {
        $data = $response->json();

        return new self(
            success: $response->successful() && ($data['success'] ?? true),
            token: $data['data']['token'] ?? $data['token'] ?? null,
            refreshToken: $data['data']['refresh_token'] ?? $data['refresh_token'] ?? null,
            user: isset($data['data']['user']) ? UserData::fromArray($data['data']['user']) : 
                  (isset($data['user']) ? UserData::fromArray($data['user']) : null),
            businesses: $data['data']['businesses'] ?? $data['businesses'] ?? null,
            roles: $data['data']['roles'] ?? $data['roles'] ?? null,
            permissions: $data['data']['permissions'] ?? $data['permissions'] ?? null,
            message: $data['message'] ?? null,
            errors: $data['errors'] ?? null
        );
    }

    /**
     * Check if login was successful
     */
    public function isSuccessful(): bool
    {
        return $this->success && !empty($this->token);
    }

    /**
     * Get the authentication token
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * Get the user data
     */
    public function getUser(): ?UserData
    {
        return $this->user;
    }

    /**
     * Get the businesses data
     */
    public function getBusinesses(): ?array
    {
        return $this->businesses;
    }

    /**
     * Get error messages
     */
    public function getErrors(): ?array
    {
        return $this->errors;
    }

    /**
     * Get the roles data
     */
    public function getRoles(): ?array
    {
        return $this->roles;
    }

    /**
     * Get the permissions data
     */
    public function getPermissions(): ?array
    {
        return $this->permissions;
    }
}
