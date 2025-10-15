<?php

use Livewire\Volt\Component;
use App\Http\Integrations\TelconApiConnector;
use App\Http\Integrations\Requests\GetTransactionStatisticsRequest;
use App\Http\Integrations\Data\TransactionStatisticsResponse;

new class extends Component {
    public $loading = true;
    public $error = null;
    public $businessId;
    
    // Individual statistics properties
    public $totalTransactions = 0;
    public $activeTransactions = 0;
    public $pendingCount = 0;
    public $inProgressCount = 0;
    public $completedCount = 0;
    public $cancelledCount = 0;
    public $refundedCount = 0;
    public $totalCost = '0.00';
    public $totalRevenue = '0.00';
    public $totalProfit = '0.00';

    public function mount()
    {
        $this->businessId = session('selected_business')['id']; // Default to 4 if not set
        $this->loadStatistics();
    }

    public function loadStatistics()
    {
        try {
            $this->loading = true;
            $this->error = null;

            $connector = new TelconApiConnector();
            $token = session('auth_token');

            if (!$token) {
                $this->error = 'Authentication token not found. Please login again.';
                $this->loading = false;
                return;
            }

            $request = new GetTransactionStatisticsRequest($this->businessId, $token);
          
            $response = $connector->send($request);
            
            if ($response->successful()) {
                $statistics = TransactionStatisticsResponse::fromResponse($response);
               
                // Populate individual properties
                $this->totalTransactions = $statistics->getTotalTransactions();
                $this->activeTransactions = $statistics->getActiveTransactions();
                $this->pendingCount = $statistics->getPendingCount();
                $this->inProgressCount = $statistics->getInProgressCount();
                $this->completedCount = $statistics->getCompletedCount();
                $this->cancelledCount = $statistics->getCancelledCount();
                $this->refundedCount = $statistics->getRefundedCount();
                $this->totalCost = $statistics->getTotalCost();
                $this->totalRevenue = $statistics->getTotalRevenue();
                $this->totalProfit = $statistics->getTotalProfit();
                
                \Log::info('Transaction statistics loaded successfully', [
                    'business_id' => $this->businessId,
                    'total_transactions' => $this->totalTransactions
                ]);
            } else {
                $this->error = 'Failed to load statistics. Status: ' . $response->status();
                \Log::error('Failed to load transaction statistics', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            $this->error = 'Error loading statistics: ' . $e->getMessage();
            \Log::error('Exception loading transaction statistics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } finally {
            $this->loading = false;
        }
    }

    public function refreshStatistics()
    {
        $this->loadStatistics();
    }
}; ?>

<div>
    <!-- Loading State -->
    @if($loading)
        <div class="flex justify-center items-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span class="ml-2 text-gray-600">Loading statistics...</span>
        </div>
    @elseif($error)
        <div class="bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Error Loading Statistics</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <p>{{ $error }}</p>
                    </div>
                    <div class="mt-4">
                        <button wire:click="refreshStatistics" class="bg-red-100 px-3 py-2 rounded-md text-sm font-medium text-red-800 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-red-500">
                            Try Again
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Header with Refresh Button -->
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Transaction Statistics
            </h3>
            <button wire:click="refreshStatistics" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Refresh
            </button>
        </div>

        <!-- Overview Stats -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-blue-600">Total Transactions</p>
                        <p class="text-2xl font-semibold text-blue-900">{{ $totalTransactions }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-green-50 p-4 rounded-lg">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-green-600">Active Transactions</p>
                        <p class="text-2xl font-semibold text-green-900">{{ $activeTransactions }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-yellow-50 p-4 rounded-lg">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-yellow-600">Pending</p>
                        <p class="text-2xl font-semibold text-yellow-900">{{ $pendingCount }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-purple-50 p-4 rounded-lg">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-purple-600">Completed</p>
                        <p class="text-2xl font-semibold text-purple-900">{{ $completedCount }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Breakdown -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 mb-8">
            <div class="bg-gray-50 p-4 rounded-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">In Progress</p>
                        <p class="text-xl font-semibold text-gray-900">{{ $inProgressCount }}</p>
                    </div>
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-red-50 p-4 rounded-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-red-600">Cancelled</p>
                        <p class="text-xl font-semibold text-red-900">{{ $cancelledCount }}</p>
                    </div>
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-orange-50 p-4 rounded-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-orange-600">Refunded</p>
                        <p class="text-xl font-semibold text-orange-900">{{ $refundedCount }}</p>
                    </div>
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Summary -->
        <div class="border-t pt-6">
            <h4 class="text-md font-medium text-gray-900 mb-4">Financial Summary</h4>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div class="bg-red-50 p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-600">Total Cost</p>
                            <p class="text-lg font-semibold text-red-900">₵{{ $totalCost }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-600">Total Revenue</p>
                            <p class="text-lg font-semibold text-green-900">₵{{ $totalRevenue }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-blue-600">Total Profit</p>
                            <p class="text-lg font-semibold text-blue-900">₵{{ $totalProfit }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
