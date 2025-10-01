<?php

namespace App\Services;

use App\Http\Integrations\TelconApiConnector;
use App\Http\Integrations\Requests\NetworkRequest;
use App\Http\Integrations\Requests\NetworkServicesRequest;
use App\Http\Integrations\Requests\NetworkServicePricingRequest;
use App\Http\Integrations\Requests\ActiveNetworkServicesRequest;
use App\Http\Integrations\Requests\CreateNetworkServicePricingRequest;
use App\Http\Integrations\Requests\StockBatchesByBusinessRequest;
use App\Http\Integrations\Requests\CreateStockBatchRequest;
use App\Http\Integrations\Requests\CreateStockItemRequest;
use App\Http\Integrations\Data\NetworkResponse;
use App\Http\Integrations\Data\NetworkServicesResponse;
use App\Http\Integrations\Data\NetworkServicePricingResponse;
use App\Http\Integrations\Data\ActiveNetworkServicesResponse;
use App\Http\Integrations\Data\CreateNetworkServicePricingResponse;
use App\Http\Integrations\Data\StockBatchesResponse;
use App\Http\Integrations\Data\CreateStockBatchResponse;
use App\Http\Integrations\Data\CreateStockItemResponse;
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

    /**
     * Get active network services
     */
    public function getActiveNetworkServices(): ActiveNetworkServicesResponse
    {
        try {
            $token = Session::get('auth_token');
            
            if (!$token) {
                Log::warning('No auth token found for active network services request');
                return new ActiveNetworkServicesResponse(
                    success: false,
                    message: 'Authentication token not found',
                    data: null,
                    errors: ['auth' => 'Authentication token not found']
                );
            }
            
            $request = new ActiveNetworkServicesRequest($token);
            
            Log::info('Making active network services request', [
                'endpoint' => $request->resolveEndpoint(),
                'method' => $request->getMethod()->value,
                'has_token' => !empty($token)
            ]);
            
            $response = $this->connector->send($request);
            
            Log::info('Active Network Services API response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $response->body(),
                'headers' => $response->headers()
            ]);
            
            $servicesResponse = ActiveNetworkServicesResponse::fromResponse($response);
            
            if ($servicesResponse->isSuccessful()) {
                Log::info('Active network services retrieved successfully', [
                    'services_count' => count($servicesResponse->getServices())
                ]);
            } else {
                Log::warning('Failed to retrieve active network services', [
                    'errors' => $servicesResponse->getErrors(),
                    'response_status' => $response->status(),
                    'response_body' => $response->body()
                ]);
            }
            
            return $servicesResponse;
            
        } catch (\Exception $e) {
            Log::error('Active network services request failed', [
                'error' => $e->getMessage()
            ]);
            
            return new ActiveNetworkServicesResponse(
                success: false,
                message: 'Active network services request failed. Please try again.',
                data: null,
                errors: ['network' => 'Unable to connect to active network services service']
            );
        }
    }

    /**
     * Create or update network service pricing
     */
    public function createNetworkServicePricing(int $networkServiceId, int $businessId, float $costPrice, float $sellingPrice): CreateNetworkServicePricingResponse
    {
        try {
            $token = Session::get('auth_token');
            
            if (!$token) {
                Log::warning('No auth token found for create network service pricing request');
                return new CreateNetworkServicePricingResponse(
                    success: false,
                    message: 'Authentication token not found',
                    data: null,
                    errors: ['auth' => 'Authentication token not found']
                );
            }
            
            $request = new CreateNetworkServicePricingRequest($token, $networkServiceId, $businessId, $costPrice, $sellingPrice);
            
            Log::info('Making create network service pricing request', [
                'network_service_id' => $networkServiceId,
                'business_id' => $businessId,
                'cost_price' => $costPrice,
                'selling_price' => $sellingPrice,
                'endpoint' => $request->resolveEndpoint(),
                'method' => $request->getMethod()->value,
                'has_token' => !empty($token)
            ]);
            
            $response = $this->connector->send($request);
            
            Log::info('Create Network Service Pricing API response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $response->body(),
                'headers' => $response->headers()
            ]);
            
            $pricingResponse = CreateNetworkServicePricingResponse::fromResponse($response);
            
            if ($pricingResponse->isSuccessful()) {
                Log::info('Network service pricing created/updated successfully', [
                    'pricing_id' => $pricingResponse->getPricingId(),
                    'network_service_id' => $networkServiceId,
                    'business_id' => $businessId
                ]);
            } else {
                Log::warning('Failed to create/update network service pricing', [
                    'errors' => $pricingResponse->getErrors(),
                    'response_status' => $response->status(),
                    'response_body' => $response->body()
                ]);
            }
            
            return $pricingResponse;
            
        } catch (\Exception $e) {
            Log::error('Create network service pricing request failed', [
                'network_service_id' => $networkServiceId,
                'business_id' => $businessId,
                'error' => $e->getMessage()
            ]);
            
            return new CreateNetworkServicePricingResponse(
                success: false,
                message: 'Create network service pricing request failed. Please try again.',
                data: null,
                errors: ['network' => 'Unable to connect to create network service pricing service']
            );
        }
    }

    /**
     * Get stock batches by business
     */
    public function getStockBatchesByBusiness(int $businessId): StockBatchesResponse
    {
        try {
            $token = Session::get('auth_token');
            
            if (!$token) {
                Log::warning('No auth token found for stock batches request');
                return new StockBatchesResponse(
                    success: false,
                    message: 'Authentication token not found',
                    data: null,
                    errors: ['auth' => 'Authentication token not found']
                );
            }
            
            $request = new StockBatchesByBusinessRequest($token, $businessId);
            
            Log::info('Making stock batches request', [
                'business_id' => $businessId,
                'endpoint' => $request->resolveEndpoint(),
                'method' => $request->getMethod()->value,
                'has_token' => !empty($token)
            ]);
            
            $response = $this->connector->send($request);
            
            Log::info('Stock Batches API response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $response->body(),
                'headers' => $response->headers()
            ]);
            
            $batchesResponse = StockBatchesResponse::fromResponse($response);
            
            if ($batchesResponse->isSuccessful()) {
                Log::info('Stock batches retrieved successfully', [
                    'batches_count' => count($batchesResponse->getBatches())
                ]);
            } else {
                Log::warning('Failed to retrieve stock batches', [
                    'errors' => $batchesResponse->getErrors(),
                    'response_status' => $response->status(),
                    'response_body' => $response->body()
                ]);
            }
            
            return $batchesResponse;
            
        } catch (\Exception $e) {
            Log::error('Stock batches request failed', [
                'business_id' => $businessId,
                'error' => $e->getMessage()
            ]);
            
            return new StockBatchesResponse(
                success: false,
                message: 'Stock batches request failed. Please try again.',
                data: null,
                errors: ['network' => 'Unable to connect to stock batches service']
            );
        }
    }

    /**
     * Create stock batch
     */
    public function createStockBatch(
        string $name,
        string $description,
        string $boxBatchNumber,
        string $startingIccid,
        string $endingIccid,
        int $quantity,
        float $cost,
        int $businessId,
        string $networkId
    ): CreateStockBatchResponse {
        try {
            $token = Session::get('auth_token');
            
            if (!$token) {
                Log::warning('No auth token found for create stock batch request');
                return new CreateStockBatchResponse(
                    success: false,
                    message: 'Authentication token not found',
                    data: null,
                    errors: ['auth' => 'Authentication token not found']
                );
            }
            
            $request = new CreateStockBatchRequest(
                $token,
                $name,
                $description,
                $boxBatchNumber,
                $startingIccid,
                $endingIccid,
                $quantity,
                $cost,
                $businessId,
                $networkId
            );
            
            Log::info('Making create stock batch request', [
                'name' => $name,
                'quantity' => $quantity,
                'cost' => $cost,
                'business_id' => $businessId,
                'network_id' => $networkId,
                'endpoint' => $request->resolveEndpoint(),
                'method' => $request->getMethod()->value,
                'has_token' => !empty($token)
            ]);
            
            $response = $this->connector->send($request);
            
            Log::info('Create Stock Batch API response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $response->body(),
                'headers' => $response->headers()
            ]);
            
            $batchResponse = CreateStockBatchResponse::fromResponse($response);
            
            if ($batchResponse->isSuccessful()) {
                Log::info('Stock batch created successfully', [
                    'batch_id' => $batchResponse->getBatchId(),
                    'name' => $name,
                    'business_id' => $businessId
                ]);
            } else {
                Log::warning('Failed to create stock batch', [
                    'errors' => $batchResponse->getErrors(),
                    'response_status' => $response->status(),
                    'response_body' => $response->body()
                ]);
            }
            
            return $batchResponse;
            
        } catch (\Exception $e) {
            Log::error('Create stock batch request failed', [
                'name' => $name,
                'business_id' => $businessId,
                'network_id' => $networkId,
                'error' => $e->getMessage()
            ]);
            
            return new CreateStockBatchResponse(
                success: false,
                message: 'Create stock batch request failed. Please try again.',
                data: null,
                errors: ['network' => 'Unable to connect to create stock batch service']
            );
        }
    }

    /**
     * Create stock items
     */
    public function createStockItems(int $stockBatchId, string $serialNumbers): CreateStockItemResponse
    {
        try {
            $token = Session::get('auth_token');
            
            if (!$token) {
                Log::warning('No auth token found for create stock items request');
                return new CreateStockItemResponse(
                    success: false,
                    message: 'Authentication token not found',
                    data: null,
                    errors: ['auth' => 'Authentication token not found']
                );
            }
            
            $request = new CreateStockItemRequest($token, $stockBatchId, $serialNumbers);
            
            Log::info('Making create stock items request', [
                'stock_batch_id' => $stockBatchId,
                'serial_numbers_length' => strlen($serialNumbers),
                'endpoint' => $request->resolveEndpoint(),
                'method' => $request->getMethod()->value,
                'has_token' => !empty($token)
            ]);
            
            $response = $this->connector->send($request);
            
            Log::info('Create Stock Items API response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $response->body(),
                'headers' => $response->headers()
            ]);
            
            $itemsResponse = CreateStockItemResponse::fromResponse($response);
            
            if ($itemsResponse->isSuccessful()) {
                Log::info('Stock items created successfully', [
                    'stock_batch_id' => $stockBatchId,
                    'items_count' => $itemsResponse->getItemsCount()
                ]);
            } else {
                Log::warning('Failed to create stock items', [
                    'errors' => $itemsResponse->getErrors(),
                    'response_status' => $response->status(),
                    'response_body' => $response->body()
                ]);
            }
            
            return $itemsResponse;
            
        } catch (\Exception $e) {
            Log::error('Create stock items request failed', [
                'stock_batch_id' => $stockBatchId,
                'error' => $e->getMessage()
            ]);
            
            return new CreateStockItemResponse(
                success: false,
                message: 'Create stock items request failed. Please try again.',
                data: null,
                errors: ['network' => 'Unable to connect to create stock items service']
            );
        }
    }
}
