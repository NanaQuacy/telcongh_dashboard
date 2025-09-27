<?php

namespace App\Http\Integrations\Data;

use Saloon\Http\Response;

class RegisterResponse
{
    public function __construct(
        public bool $success,
        public string $message,
        public ?array $user = null,
        public ?string $token = null,
        public array $errors = []
    ) {}

    /**
     * Create RegisterResponse from API response
     */
    public static function fromResponse(Response $response): self
    {
        $data = $response->json();
        
        if ($response->successful()) {
            return new self(
                success: true,
                message: $data['message'] ?? 'Registration successful',
                user: $data['user'] ?? null,
                token: $data['token'] ?? null,
                errors: []
            );
        }
        
        return new self(
            success: false,
            message: $data['message'] ?? 'Registration failed',
            user: null,
            token: null,
            errors: $data['errors'] ?? ['registration' => 'Registration failed']
        );
    }

    /**
     * Check if registration was successful
     */
    public function isSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * Get user data
     */
    public function getUser(): ?array
    {
        return $this->user;
    }

    /**
     * Get authentication token
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * Get error messages
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get success message
     */
    public function getMessage(): string
    {
        return $this->message;
    }
}
