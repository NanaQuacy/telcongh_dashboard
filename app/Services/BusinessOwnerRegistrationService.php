<?php

namespace App\Services;

use App\Http\Integrations\Data\RegisterBusinessOwnerResponse;
use App\Http\Integrations\Requests\RegisterBusinessOwnerRequest;
use App\Http\Integrations\TelconApiConnector;
use Illuminate\Support\Facades\Log;

class BusinessOwnerRegistrationService
{
    protected TelconApiConnector $connector;

    public function __construct()
    {
        $this->connector = new TelconApiConnector();
    }

    /**
     * Register a new business owner with their business
     */
    public function registerBusinessOwner(array $registrationData): ?RegisterBusinessOwnerResponse
    {
        try {
            // Validate the registration data
            $request = RegisterBusinessOwnerRequest::fromArray($registrationData);
            $validationErrors = $request->validatePayload();

            if (!empty($validationErrors)) {
                Log::error('Registration validation failed', [
                    'errors' => $validationErrors,
                    'data' => $registrationData
                ]);

                return new RegisterBusinessOwnerResponse(
                    success: false,
                    message: 'Validation failed',
                    user: null,
                    business: null,
                    token: null,
                    errors: $validationErrors
                );
            }

            // Send the registration request
            $response = $this->connector->send($request);

            if ($response->successful()) {
                $registrationResponse = RegisterBusinessOwnerResponse::fromResponse($response);
                
                Log::info('Business owner registration successful', [
                    'user_id' => $registrationResponse->getUserId(),
                    'business_id' => $registrationResponse->getBusinessId(),
                    'email' => $registrationResponse->getUserEmail()
                ]);

                return $registrationResponse;
            }

            Log::error('Registration request failed', [
                'status' => $response->status(),
                'response' => $response->body(),
                'data' => $registrationData
            ]);

            // Try to parse error response
            $errorData = $response->json();
            return new RegisterBusinessOwnerResponse(
                success: false,
                message: $errorData['message'] ?? 'Registration failed',
                user: null,
                business: null,
                token: null,
                errors: $errorData['errors'] ?? ['general' => 'Registration failed']
            );

        } catch (\Exception $e) {
            Log::error('Exception during business owner registration', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $registrationData
            ]);

            return new RegisterBusinessOwnerResponse(
                success: false,
                message: 'Registration failed: ' . $e->getMessage(),
                user: null,
                business: null,
                token: null,
                errors: ['general' => 'Registration failed due to server error']
            );
        }
    }

    /**
     * Register business owner with individual parameters
     */
    public function registerBusinessOwnerWithParams(
        string $name,
        string $phone,
        string $email,
        string $password,
        string $passwordConfirmation,
        string $businessName,
        string $businessAddress,
        string $businessPhone,
        string $businessEmail,
        ?string $businessWebsite = null,
        ?string $businessDescription = null
    ): ?RegisterBusinessOwnerResponse {
        $registrationData = [
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
            'business_name' => $businessName,
            'business_address' => $businessAddress,
            'business_phone' => $businessPhone,
            'business_email' => $businessEmail,
            'business_website' => $businessWebsite,
            'business_description' => $businessDescription,
        ];

        return $this->registerBusinessOwner($registrationData);
    }

    /**
     * Validate registration data without making the request
     */
    public function validateRegistrationData(array $registrationData): array
    {
        $request = RegisterBusinessOwnerRequest::fromArray($registrationData);
        return $request->validatePayload();
    }

    /**
     * Check if email is already registered
     */
    public function isEmailRegistered(string $email): bool
    {
        // This would typically make a request to check if email exists
        // For now, we'll return false as we don't have a specific endpoint for this
        return false;
    }

    /**
     * Check if phone is already registered
     */
    public function isPhoneRegistered(string $phone): bool
    {
        // This would typically make a request to check if phone exists
        // For now, we'll return false as we don't have a specific endpoint for this
        return false;
    }
}
