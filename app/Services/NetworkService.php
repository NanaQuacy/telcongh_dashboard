<?php

namespace App\Services;

use App\Http\Integrations\TelconApiConnector;
use App\Http\Integrations\Requests\NetworkRequest;
use App\Http\Integrations\Requests\NetworkServicesRequest;
use App\Http\Integrations\Requests\NetworkServicePricingRequest;
use App\Http\Integrations\Data\NetworkResponse;
use App\Http\Integrations\Data\NetworkServicesResponse;
use App\Http\Integrations\Data\NetworkServicePricingResponse;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class NetworkService
{
    public function __construct(
        private TelconApiConnector $connector
    ) {}

    public function getAllNetworks(?int $page = null, ?int $perPage = null): NetworkResponse
    {
        try {
            $token = Session::get('auth_token');
            if (!$token) {
                Log::warning('No auth token found for network request');
                return new NetworkResponse(
                    success: false,
                    message: 'Authentication token not found',
                    networks: [],
                    pagination: [],
                    errors: ['auth' => 'Authentication token not found']
                );
            }

            $request = new NetworkRequest($token, $page, $perPage);
            
            Log::info('Making network request', [
                'endpoint' => $request->resolveEndpoint(),
                'method' => $request->getMethod()->value,
                'query_params' => ['page' => $page, 'per_page' => $perPage],
                'has_token' => !empty($token)
            ]);

            $response = $this->connector->send($request);
            
            Log::info('Network API response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $response->body(),
                'headers' => $response->headers()
            ]);

            return NetworkResponse::fromResponse($response);

        } catch (\Exception $e) {
            Log::error('Network request failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new NetworkResponse(
                success: false,
                message: 'Failed to fetch networks: ' . $e->getMessage(),
                networks: [],
                pagination: [],
                errors: ['exception' => $e->getMessage()]
            );
        }
    }

    public function getNetworksForCurrentUser(?int $page = null, ?int $perPage = null): NetworkResponse
    {
        $userId = Session::get('user_id');
        
        Log::info('Getting networks for current user', [
            'user_id' => $userId,
            'page' => $page,
            'per_page' => $perPage
        ]);

        if (!$userId) {
            Log::warning('No user ID found in session for network request');
            return new NetworkResponse(
                success: false,
                message: 'User not authenticated',
                networks: [],
                pagination: [],
                errors: ['auth' => 'User not authenticated']
            );
        }

        return $this->getAllNetworks($page, $perPage);
    }

    public function getNetworkById(string $networkId): NetworkResponse
    {
        try {
            $token = Session::get('auth_token');
            if (!$token) {
                Log::warning('No auth token found for network request', ['network_id' => $networkId]);
                return new NetworkResponse(
                    success: false,
                    message: 'Authentication token not found',
                    networks: [],
                    pagination: [],
                    errors: ['auth' => 'Authentication token not found']
                );
            }

            // Create a custom request for single network
            $request = new NetworkRequest($token);
            $request->resolveEndpoint = fn() => "/networks/{$networkId}";
            
            Log::info('Making single network request', [
                'network_id' => $networkId,
                'endpoint' => "/networks/{$networkId}",
                'has_token' => !empty($token)
            ]);

            $response = $this->connector->send($request);
            
            Log::info('Single network API response', [
                'network_id' => $networkId,
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $response->body()
            ]);

            return NetworkResponse::fromResponse($response);

        } catch (\Exception $e) {
            Log::error('Single network request failed', [
                'network_id' => $networkId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new NetworkResponse(
                success: false,
                message: 'Failed to fetch network: ' . $e->getMessage(),
                networks: [],
                pagination: [],
                errors: ['exception' => $e->getMessage()]
            );
        }
    }

    public function getServicesByNetwork(int $businessId, ?int $page = null, ?int $perPage = null): NetworkServicesResponse
    {
        try {
            $token = Session::get('auth_token');
            if (!$token) {
                Log::warning('No auth token found for network services request', ['network_id' => $networkId]);
                return new NetworkServicesResponse(
                    success: false,
                    message: 'Authentication token not found',
                    services: [],
                    pagination: [],
                    errors: ['auth' => 'Authentication token not found']
                );
            }

            $request = new NetworkServicesRequest($token, $businessId, $page, $perPage);
            
            Log::info('Making network services request', [
                'business_id' => $businessId,
                'endpoint' => $request->resolveEndpoint(),
                'method' => $request->getMethod()->value,
                'query_params' => ['page' => $page, 'per_page' => $perPage],
                'has_token' => !empty($token)
            ]);

            $response = $this->connector->send($request);
            
            Log::info('Network services API response', [
                'business_id' => $businessId,
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $response->body(),
                'headers' => $response->headers()
            ]);

            return NetworkServicesResponse::fromResponse($response);

        } catch (\Exception $e) {
            Log::error('Network services request failed', [
                'business_id' => $businessId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new NetworkServicesResponse(
                success: false,
                message: 'Failed to fetch network services: ' . $e->getMessage(),
                services: [],
                pagination: [],
                errors: ['exception' => $e->getMessage()]
            );
        }
    }

    /**
     * Get a specific network service pricing by ID
     */
    public function getNetworkServicePricing(int $id): NetworkServicePricingResponse
    {
        try {
            $token = Session::get('auth_token');
            
            if (!$token) {
                Log::warning('No auth token found for network service pricing request', ['id' => $id]);
                return new NetworkServicePricingResponse(
                    success: false,
                    message: 'Authentication token not found',
                    data: null,
                    errors: ['auth' => 'Authentication token not found']
                );
            }
            
            $request = new NetworkServicePricingRequest($token, $id);
            
            // Debug: Log the request details
            Log::info('Making network service pricing request', [
                'id' => $id,
                'endpoint' => $request->resolveEndpoint(),
                'method' => $request->getMethod()->value,
                'has_token' => !empty($token)
            ]);
            
            $response = $this->connector->send($request);
            
            // Debug: Log the response details
            Log::info('Network Service Pricing API response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $response->body(),
                'headers' => $response->headers()
            ]);
            
            $pricingResponse = NetworkServicePricingResponse::fromResponse($response);
            
            if ($pricingResponse->isSuccessful()) {
                Log::info('Network service pricing retrieved successfully', [
                    'id' => $id,
                    'service_name' => $pricingResponse->getServiceName(),
                    'network_name' => $pricingResponse->getNetworkName()
                ]);
            } else {
                Log::warning('Failed to retrieve network service pricing', [
                    'id' => $id,
                    'errors' => $pricingResponse->getErrors(),
                    'response_status' => $response->status(),
                    'response_body' => $response->body()
                ]);
            }
            
            return $pricingResponse;
            
        } catch (\Exception $e) {
            Log::error('Network service pricing request failed', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return new NetworkServicePricingResponse(
                success: false,
                message: 'Network service pricing request failed. Please try again.',
                data: null,
                errors: ['network' => 'Unable to connect to network service pricing service']
            );
        }
    }
}
