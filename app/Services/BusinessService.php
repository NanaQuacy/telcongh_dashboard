<?php

namespace App\Services;

use App\Http\Integrations\TelconApiConnector;
use App\Http\Integrations\Requests\BusinessRequest;
use App\Http\Integrations\Data\BusinessResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class BusinessService
{
    protected TelconApiConnector $connector;

    public function __construct(TelconApiConnector $connector)
    {
        $this->connector = $connector;
    }

    /**
     * Get businesses for a specific user
     */
    public function getUserBusinesses(string $userId): BusinessResponse
    {
        try {
            $token = Session::get('auth_token');
            
            if (!$token) {
                Log::warning('No auth token found for business request', ['user_id' => $userId]);
                return new BusinessResponse(
                    success: false,
                    message: 'Authentication token not found',
                    businesses: [],
                    errors: ['auth' => 'Authentication token not found']
                );
            }
            
            $request = new BusinessRequest($userId, $token);
            
            // Debug: Log the request details
            Log::info('Making business request', [
                'user_id' => $userId,
                'endpoint' => $request->resolveEndpoint(),
                'method' => $request->getMethod()->value,
                'query_params' => ['user_id' => $userId],
                'has_token' => !empty($token)
            ]);
            
            $response = $this->connector->send($request);
            
            // Debug: Log the response details
            Log::info('Business API response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $response->body(),
                'headers' => $response->headers()
            ]);
            
            $businessResponse = BusinessResponse::fromResponse($response);
            
            if ($businessResponse->isSuccessful()) {
                Log::info('Businesses retrieved successfully', [
                    'user_id' => $userId,
                    'business_count' => $businessResponse->getBusinessCount()
                ]);
            } else {
                Log::warning('Failed to retrieve businesses', [
                    'user_id' => $userId,
                    'errors' => $businessResponse->getErrors(),
                    'response_status' => $response->status(),
                    'response_body' => $response->body()
                ]);
            }
            
            return $businessResponse;
            
        } catch (\Exception $e) {
            Log::error('Business request failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return new BusinessResponse(
                success: false,
                message: 'Business request failed. Please try again.',
                businesses: [],
                errors: ['network' => 'Unable to connect to business service']
            );
        }
    }

    /**
     * Get businesses for the current authenticated user
     */
    public function getCurrentUserBusinesses(): BusinessResponse
    {
        $userId = Session::get('user_id');
        
        // Debug: Log session data
        Log::info('Session data for business request', [
            'user_id' => $userId,
            'auth_token' => Session::get('auth_token'),
            'authenticated' => Session::get('authenticated'),
            'all_session_keys' => array_keys(Session::all())
        ]);
        
        if (!$userId) {
            Log::warning('No user_id found in session for business request');
            return new BusinessResponse(
                success: false,
                message: 'User not authenticated',
                businesses: [],
                errors: ['auth' => 'User not authenticated']
            );
        }
        
        return $this->getUserBusinesses((string) $userId);
    }

    /**
     * Get a specific business by ID for a user
     */
    public function getUserBusiness(string $userId, string $businessId): ?array
    {
        $businessResponse = $this->getUserBusinesses($userId);
        
        if (!$businessResponse->isSuccessful()) {
            return null;
        }
        
        return $businessResponse->getBusinessById($businessId);
    }

    /**
     * Get the currently selected business from session
     */
    public function getCurrentBusiness(): ?array
    {
        $userId = Session::get('user_id');
        $businessId = Session::get('selected_business_id');
        
        if (!$userId || !$businessId) {
            return null;
        }
        
        return $this->getUserBusiness($userId, $businessId);
    }

    /**
     * Set the current business in session
     */
    public function setCurrentBusiness(string $businessId): void
    {
        Session::put('selected_business_id', $businessId);
        
        Log::info('Current business updated', [
            'business_id' => $businessId,
            'user_id' => Session::get('user_id')
        ]);
    }

    /**
     * Clear the current business from session
     */
    public function clearCurrentBusiness(): void
    {
        Session::forget('selected_business_id');
        
        Log::info('Current business cleared', [
            'user_id' => Session::get('user_id')
        ]);
    }
}
