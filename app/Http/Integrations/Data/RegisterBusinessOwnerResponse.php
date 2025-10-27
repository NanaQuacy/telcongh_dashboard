<?php

namespace App\Http\Integrations\Data;

use Saloon\Http\Response;

class RegisterBusinessOwnerResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $message,
        public readonly ?array $user,
        public readonly ?array $business,
        public readonly ?string $token,
        public readonly ?array $errors
    ) {}

    public static function fromResponse(Response $response): self
    {
        $data = $response->json();

        // Handle different response structures
        if (isset($data['data'])) {
            $responseData = $data['data'];
        } else {
            $responseData = $data;
        }

        return new self(
            success: $data['success'] ?? $response->successful(),
            message: $data['message'] ?? $responseData['message'] ?? null,
            user: $responseData['user'] ?? null,
            business: $responseData['business'] ?? null,
            token: $responseData['token'] ?? $responseData['access_token'] ?? null,
            errors: $data['errors'] ?? $responseData['errors'] ?? null
        );
    }

    public function isSuccessful(): bool
    {
        return $this->success;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getUser(): ?array
    {
        return $this->user;
    }

    public function getBusiness(): ?array
    {
        return $this->business;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function getErrors(): ?array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getUserId(): ?int
    {
        return $this->user['id'] ?? null;
    }

    public function getUserName(): ?string
    {
        return $this->user['name'] ?? null;
    }

    public function getUserEmail(): ?string
    {
        return $this->user['email'] ?? null;
    }

    public function getBusinessId(): ?int
    {
        return $this->business['id'] ?? null;
    }

    public function getBusinessName(): ?string
    {
        return $this->business['name'] ?? null;
    }

    public function getBusinessCode(): ?string
    {
        return $this->business['business_code'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'user' => $this->user,
            'business' => $this->business,
            'token' => $this->token,
            'errors' => $this->errors,
        ];
    }
}
