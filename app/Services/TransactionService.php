<?php

namespace App\Services;

use App\Http\Integrations\Data\TransactionListResponse;
use App\Http\Integrations\Data\TransactionStatisticsResponse;
use App\Http\Integrations\Requests\GetTransactionRequest;
use App\Http\Integrations\Requests\GetTransactionStatisticsRequest;
use App\Http\Integrations\TelconApiConnector;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    protected TelconApiConnector $connector;

    public function __construct()
    {
        $this->connector = new TelconApiConnector();
    }

    /**
     * Get business transactions with filtering and pagination
     */
    public function getBusinessTransactions(
        int $businessId,
        ?string $token = null,
        array $filters = []
    ): ?TransactionListResponse {
        try {
            $request = new GetTransactionRequest($businessId, null, $token, $filters);
            $response = $this->connector->send($request);

            if ($response->successful()) {
                return TransactionListResponse::fromResponse($response);
            }

            Log::error('Failed to fetch business transactions', [
                'business_id' => $businessId,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Exception in getBusinessTransactions', [
                'business_id' => $businessId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }

    /**
     * Get transaction statistics for a business
     */
    public function getTransactionStatistics(
        int $businessId,
        ?string $token = null
    ): ?TransactionStatisticsResponse {
        try {
            $request = new GetTransactionStatisticsRequest($businessId, $token);
            $response = $this->connector->send($request);

            if ($response->successful()) {
                return TransactionStatisticsResponse::fromResponse($response);
            }

            Log::error('Failed to fetch transaction statistics', [
                'business_id' => $businessId,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Exception in getTransactionStatistics', [
                'business_id' => $businessId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }

    /**
     * Get transactions for today
     */
    public function getTodayTransactions(
        int $businessId,
        ?string $token = null,
        array $additionalFilters = []
    ): ?TransactionListResponse {
        $filters = array_merge($additionalFilters, [
            'date_from' => now()->startOfDay()->toDateString(),
            'date_to' => now()->endOfDay()->toDateString()
        ]);

        return $this->getBusinessTransactions($businessId, $token, $filters);
    }

    /**
     * Get transactions for yesterday
     */
    public function getYesterdayTransactions(
        int $businessId,
        ?string $token = null,
        array $additionalFilters = []
    ): ?TransactionListResponse {
        $filters = array_merge($additionalFilters, [
            'date_from' => now()->subDay()->startOfDay()->toDateString(),
            'date_to' => now()->subDay()->endOfDay()->toDateString()
        ]);

        return $this->getBusinessTransactions($businessId, $token, $filters);
    }

    /**
     * Get transactions for this week
     */
    public function getThisWeekTransactions(
        int $businessId,
        ?string $token = null,
        array $additionalFilters = []
    ): ?TransactionListResponse {
        $filters = array_merge($additionalFilters, [
            'date_from' => now()->startOfWeek()->toDateString(),
            'date_to' => now()->endOfWeek()->toDateString()
        ]);

        return $this->getBusinessTransactions($businessId, $token, $filters);
    }

    /**
     * Get transactions for this month
     */
    public function getThisMonthTransactions(
        int $businessId,
        ?string $token = null,
        array $additionalFilters = []
    ): ?TransactionListResponse {
        $filters = array_merge($additionalFilters, [
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->endOfMonth()->toDateString()
        ]);

        return $this->getBusinessTransactions($businessId, $token, $filters);
    }

    /**
     * Get transactions for custom date range
     */
    public function getCustomDateRangeTransactions(
        int $businessId,
        string $dateFrom,
        string $dateTo,
        ?string $token = null,
        array $additionalFilters = []
    ): ?TransactionListResponse {
        $filters = array_merge($additionalFilters, [
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]);

        return $this->getBusinessTransactions($businessId, $token, $filters);
    }


    /**
     * Get available transaction statuses
     */
    public function getTransactionStatuses(): array
    {
        return [
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded'
        ];
    }

    /**
     * Get available payment statuses
     */
    public function getPaymentStatuses(): array
    {
        return [
            'pending' => 'Pending',
            'partial' => 'Partial',
            'completed' => 'Completed',
            'failed' => 'Failed'
        ];
    }

    /**
     * Get date range options
     */
    public function getDateRangeOptions(): array
    {
        return [
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'this_week' => 'This Week',
            'this_month' => 'This Month',
            'custom' => 'Custom Date Range'
        ];
    }
}
