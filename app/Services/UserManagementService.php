<?php

namespace App\Services;

use App\Http\Integrations\TelconApiConnector;
use App\Http\Integrations\Requests\BusinessUsersRequest;
use App\Http\Integrations\Data\BusinessUsersListResponse;
use Illuminate\Support\Facades\Log;

class UserManagementService
{
    protected TelconApiConnector $connector;

    public function __construct(TelconApiConnector $connector)
    {
        $this->connector = $connector;
    }

    /**
     * Get all users for a specific business
     */
    public function getBusinessUsers(int $businessId, ?string $token = null, int $page = 1, int $perPage = 15): BusinessUsersListResponse
    {
        try {
            $request = new BusinessUsersRequest($businessId, $token, $page, $perPage);
            $response = $this->connector->send($request);

            // Debug: Log response details
            Log::info('GetBusinessUsers API Response', [
                'business_id' => $businessId,
                'status' => $response->status(),
                'successful' => $response->successful(),
                'page' => $page,
                'per_page' => $perPage,
                'body' => $response->body(),
                'json' => $response->json()
            ]);

            if ($response->successful()) {
                return BusinessUsersListResponse::fromResponse($response);
            }

            throw new \Exception('Failed to fetch business users: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Error fetching business users: ' . $e->getMessage());
            throw $e;
        }
    }
}
