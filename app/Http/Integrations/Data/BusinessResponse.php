<?php

namespace App\Http\Integrations\Data;

use Saloon\Http\Response;

class BusinessResponse
{
    public function __construct(
        public bool $success,
        public string $message,
        public array $businesses = [],
        public array $errors = []
    ) {}

    /**
     * Create BusinessResponse from API response
     */
    public static function fromResponse(Response $response): self
    {
        try {
            $data = $response->json();
        } catch (\Exception $e) {
            // If JSON parsing fails, return error response
            return new self(
                success: false,
                message: 'Invalid JSON response from API',
                businesses: [],
                errors: ['json' => 'Failed to parse API response: ' . $e->getMessage()]
            );
        }
        
        // Check if response is successful AND has success flag in data
        if ($response->successful() && ($data['status'] ?? $data['success'] ?? true) === 'success') {
            return new self(
                success: true,
                message: $data['message'] ?? 'Businesses retrieved successfully',
                businesses: $data['data'] ?? $data['businesses'] ?? [],
                errors: []
            );
        }
        
        return new self(
            success: false,
            message: $data['message'] ?? 'Failed to retrieve businesses',
            businesses: [],
            errors: $data['errors'] ?? ['business' => 'Failed to retrieve businesses']
        );
    }

    /**
     * Check if request was successful
     */
    public function isSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * Get businesses array
     */
    public function getBusinesses(): array
    {
        return $this->businesses;
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

    /**
     * Get business count
     */
    public function getBusinessCount(): int
    {
        return count($this->businesses);
    }

    /**
     * Get business by ID
     */
    public function getBusinessById(string $businessId): ?array
    {
        foreach ($this->businesses as $business) {
            if (($business['id'] ?? '') === $businessId) {
                return $business;
            }
        }
        
        return null;
    }
}
