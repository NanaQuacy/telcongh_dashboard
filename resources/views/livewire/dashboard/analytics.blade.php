<?php

use Livewire\Volt\Component;
use App\Services\TransactionService;
use App\Http\Integrations\Data\TransactionListResponse;
use App\Http\Integrations\Data\TransactionStatisticsResponse;
use App\Http\Integrations\Data\TransactionItemResponse;

new class extends Component {
    public $transactions = [];
    public $statistics = [];
    public $loading = false;
    public $error = null;
    public $businessId = null;
    public $businessName = '';
    public $businessCode = '';
    
    // Pagination
    public $currentPage = 1;
    public $lastPage = 1;
    public $perPage = 15;
    public $total = 0;
    public $hasNextPage = false;
    public $hasPreviousPage = false;
    
    // Filters
    public $dateRange = 'today';
    public $customDateFrom = '';
    public $customDateTo = '';
    public $transactionStatus = '';
    public $paymentStatus = '';
    public $searchTerm = '';
    
    // Services
    protected $transactionService;

    public function mount()
    {
        $this->initializeService();
        $this->loadBusinessInfo();
        $this->refreshData();
    }

    public function initializeService()
    {
        try {
            $this->transactionService = new TransactionService();
        } catch (\Exception $e) {
            $this->error = 'Failed to initialize transaction service: ' . $e->getMessage();
            $this->transactionService = null;
        }
    }

    public function loadBusinessInfo()
    {
        $selectedBusiness = session('selected_business');
        
        if ($selectedBusiness && is_array($selectedBusiness)) {
            $this->businessId = $selectedBusiness['id'] ?? null;
            $this->businessName = $selectedBusiness['name'] ?? '';
            $this->businessCode = $selectedBusiness['code'] ?? '';
        } else {
            // Fallback to individual session values if the array structure doesn't exist
            $this->businessId = session('selected_business_id');
            $this->businessName = session('selected_business_name', '');
            $this->businessCode = session('selected_business_code', '');
        }
    }

    public function refreshData()
    {
        if (!$this->businessId) {
            $this->error = 'No business selected';
            return;
        }

        if (!$this->transactionService) {
            $this->error = 'Transaction service not available';
            return;
        }

        $this->loading = true;
        $this->error = null;

        try {
            // Load statistics
            $this->loadStatistics();
            
            // Load transactions based on date range
            $this->loadTransactions();
        } catch (\Exception $e) {
            $this->error = 'Failed to load data: ' . $e->getMessage();
        } finally {
            $this->loading = false;
        }
    }

    public function loadStatistics()
    {
        $token = session('auth_token');
        $statisticsResponse = $this->transactionService->getTransactionStatistics($this->businessId, $token);
        
        if ($statisticsResponse) {
            $this->statistics = [
                'totalTransactions' => $statisticsResponse->getTotalTransactions(),
                'activeTransactions' => $statisticsResponse->getActiveTransactions(),
                'pendingCount' => $statisticsResponse->getPendingCount(),
                'inProgressCount' => $statisticsResponse->getInProgressCount(),
                'completedCount' => $statisticsResponse->getCompletedCount(),
                'cancelledCount' => $statisticsResponse->getCancelledCount(),
                'refundedCount' => $statisticsResponse->getRefundedCount(),
                'totalCost' => $statisticsResponse->getTotalCost(),
                'totalRevenue' => $statisticsResponse->getTotalRevenue(),
                'totalProfit' => $statisticsResponse->getTotalProfit(),
            ];
        } else {
            $this->statistics = [];
        }
    }

    public function loadTransactions()
    {
        $token = session('auth_token');
        $filters = $this->buildFilters();
        
        $response = null;
        
        switch ($this->dateRange) {
            case 'today':
                $response = $this->transactionService->getTodayTransactions($this->businessId, $token, $filters);
                break;
            case 'yesterday':
                $response = $this->transactionService->getYesterdayTransactions($this->businessId, $token, $filters);
                break;
            case 'this_week':
                $response = $this->transactionService->getThisWeekTransactions($this->businessId, $token, $filters);
                break;
            case 'this_month':
                $response = $this->transactionService->getThisMonthTransactions($this->businessId, $token, $filters);
                break;
            case 'custom':
                if ($this->customDateFrom && $this->customDateTo) {
                    $response = $this->transactionService->getCustomDateRangeTransactions(
                        $this->businessId,
                        $this->customDateFrom,
                        $this->customDateTo,
                        $token,
                        $filters
                    );
                }
                break;
        }

        if ($response) {
            $this->transactions = $response->getTransactions();
            $this->currentPage = $response->getCurrentPage();
            $this->lastPage = $response->getLastPage();
            $this->perPage = $response->getPerPage();
            $this->total = $response->getTotal();
            $this->hasNextPage = $response->hasNextPage();
            $this->hasPreviousPage = $response->hasPreviousPage();
        } else {
            $this->transactions = [];
            $this->error = 'Failed to load transactions';
        }
    }

    public function buildFilters(): array
    {
        $filters = [];
        
        if ($this->transactionStatus) {
            $filters['status'] = $this->transactionStatus;
        }
        
        if ($this->paymentStatus) {
            $filters['payment_status'] = $this->paymentStatus;
        }
        
        if ($this->searchTerm) {
            $filters['search'] = $this->searchTerm;
        }
        
        $filters['page'] = $this->currentPage;
        $filters['per_page'] = $this->perPage;
        
        return $filters;
    }

    public function updatedDateRange()
    {
        $this->currentPage = 1;
        $this->refreshData();
    }

    public function updatedCustomDateFrom()
    {
        $this->currentPage = 1;
        if ($this->dateRange === 'custom') {
            $this->refreshData();
        }
    }

    public function updatedCustomDateTo()
    {
        $this->currentPage = 1;
        if ($this->dateRange === 'custom') {
            $this->refreshData();
        }
    }

    public function updatedTransactionStatus()
    {
        $this->currentPage = 1;
        $this->refreshData();
    }

    public function updatedPaymentStatus()
    {
        $this->currentPage = 1;
        $this->refreshData();
    }

    public function updatedSearchTerm()
    {
        $this->currentPage = 1;
        $this->refreshData();
    }

    public function nextPage()
    {
        if ($this->hasNextPage) {
            $this->currentPage++;
            $this->loadTransactions();
        }
    }

    public function previousPage()
    {
        if ($this->hasPreviousPage) {
            $this->currentPage--;
            $this->loadTransactions();
        }
    }

    public function goToPage($page)
    {
        if ($page >= 1 && $page <= $this->lastPage) {
            $this->currentPage = $page;
            $this->loadTransactions();
        }
    }

    public function formatDate($date)
    {
        return \Carbon\Carbon::parse($date)->format('M d, Y H:i');
    }

    public function getDateRangeOptions()
    {
        if (!$this->transactionService) {
            return [
                'today' => 'Today',
                'yesterday' => 'Yesterday',
                'this_week' => 'This Week',
                'this_month' => 'This Month',
                'custom' => 'Custom Date Range'
            ];
        }
        return $this->transactionService->getDateRangeOptions();
    }

    public function getTransactionStatuses()
    {
        if (!$this->transactionService) {
            return [
                'pending' => 'Pending',
                'in_progress' => 'In Progress',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled',
                'refunded' => 'Refunded'
            ];
        }
        return $this->transactionService->getTransactionStatuses();
    }

    public function getPaymentStatuses()
    {
        if (!$this->transactionService) {
            return [
                'pending' => 'Pending',
                'partial' => 'Partial',
                'completed' => 'Completed',
                'failed' => 'Failed'
            ];
        }
        return $this->transactionService->getPaymentStatuses();
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Finance</h1>
                <livewire:dashboard.include.transactions-statistics />
            </div>
            
        </div>
    </div>

    <!-- Statistics Cards -->
    @if(!empty($statistics))
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-100 rounded-md flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Transactions</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($statistics['totalTransactions'] ?? 0) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-100 rounded-md flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                    <p class="text-2xl font-semibold text-gray-900">₵{{ number_format($statistics['totalRevenue'] ?? 0, 2) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-yellow-100 rounded-md flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Completed</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($statistics['completedCount'] ?? 0) }}</p>
                </div>
            </div>
        </div>

<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-purple-100 rounded-md flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Profit</p>
                    <p class="text-2xl font-semibold text-gray-900">₵{{ number_format($statistics['totalProfit'] ?? 0, 2) }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Filters</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Date Range -->
            <div>
                <label for="dateRange" class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                <select wire:model.live="dateRange" id="dateRange" 
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    @foreach($this->getDateRangeOptions() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Custom Date From -->
            @if($dateRange === 'custom')
            <div>
                <label for="customDateFrom" class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                <input type="date" wire:model.live="customDateFrom" id="customDateFrom"
                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Custom Date To -->
            <div>
                <label for="customDateTo" class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                <input type="date" wire:model.live="customDateTo" id="customDateTo"
                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>
            @endif

            <!-- Transaction Status -->
            <div>
                <label for="transactionStatus" class="block text-sm font-medium text-gray-700 mb-2">Transaction Status</label>
                <select wire:model.live="transactionStatus" id="transactionStatus" 
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Statuses</option>
                    @foreach($this->getTransactionStatuses() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Payment Status -->
            <div>
                <label for="paymentStatus" class="block text-sm font-medium text-gray-700 mb-2">Payment Status</label>
                <select wire:model.live="paymentStatus" id="paymentStatus" 
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Payment Statuses</option>
                    @foreach($this->getPaymentStatuses() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Search -->
            <div class="md:col-span-2 lg:col-span-4">
                <label for="searchTerm" class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <input type="text" wire:model.live.debounce.300ms="searchTerm" id="searchTerm" 
                       placeholder="Search by customer name, phone, or transaction ID..."
                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
    </div>

    <!-- Error Message -->
    @if($error)
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            {{ $error }}
        </div>
    @endif

    <!-- Loading Indicator -->
    @if($loading)
        <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded">
            <div class="flex items-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Loading transactions...
            </div>
        </div>
    @endif

    <!-- Transactions Table -->
    @if(!$loading && count($transactions) > 0)
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Transactions ({{ number_format($total) }} total)</h3>
        </div>

        <!-- Desktop Table -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($transactions as $transaction)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">#{{ $transaction->getId() }}</div>
                            @if($transaction->getServiceName())
                                <div class="text-sm text-gray-500">{{ $transaction->getServiceName() }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $transaction->getCustomerName() ?? 'N/A' }}</div>
                            @if($transaction->getCustomerPhone())
                                <div class="text-sm text-gray-500">{{ $transaction->getCustomerPhone() }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $transaction->getFormattedSellingPrice() }}</div>
                            <div class="text-sm text-gray-500">Profit: {{ $transaction->getFormattedProfit() }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $transaction->getStatusBadgeColor() }}">
                                {{ ucfirst(str_replace('_', ' ', $transaction->getTransactionStatus())) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($transaction->getPaymentStatus())
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $transaction->getPaymentStatusBadgeColor() }}">
                                    {{ ucfirst(str_replace('_', ' ', $transaction->getPaymentStatus())) }}
                                </span>
                            @else
                                <span class="text-sm text-gray-500">N/A</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $this->formatDate($transaction->getCreatedAt()) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="md:hidden">
            @foreach($transactions as $transaction)
            <div class="border-b border-gray-200 p-4">
                <div class="flex items-center justify-between mb-2">
                    <div class="text-sm font-medium text-gray-900">#{{ $transaction->getId() }}</div>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $transaction->getStatusBadgeColor() }}">
                        {{ ucfirst(str_replace('_', ' ', $transaction->getTransactionStatus())) }}
                    </span>
                </div>
                
                <div class="space-y-1 text-sm text-gray-600">
                    <div><strong>Customer:</strong> {{ $transaction->getCustomerName() ?? 'N/A' }}</div>
                    @if($transaction->getCustomerPhone())
                        <div><strong>Phone:</strong> {{ $transaction->getCustomerPhone() }}</div>
                    @endif
                    <div><strong>Amount:</strong> {{ $transaction->getFormattedSellingPrice() }}</div>
                    <div><strong>Profit:</strong> {{ $transaction->getFormattedProfit() }}</div>
                    @if($transaction->getPaymentStatus())
                        <div><strong>Payment:</strong> 
                            <span class="inline-flex px-1 py-0.5 text-xs font-semibold rounded {{ $transaction->getPaymentStatusBadgeColor() }}">
                                {{ ucfirst(str_replace('_', ' ', $transaction->getPaymentStatus())) }}
                            </span>
                        </div>
                    @endif
                    <div><strong>Date:</strong> {{ $this->formatDate($transaction->getCreatedAt()) }}</div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if($lastPage > 1)
        <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
            <div class="flex-1 flex justify-between sm:hidden">
                @if($hasPreviousPage)
                    <button wire:click="previousPage" 
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Previous
                    </button>
                @endif
                @if($hasNextPage)
                    <button wire:click="nextPage" 
                            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Next
                    </button>
                @endif
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Showing
                        <span class="font-medium">{{ (($currentPage - 1) * $perPage) + 1 }}</span>
                        to
                        <span class="font-medium">{{ min($currentPage * $perPage, $total) }}</span>
                        of
                        <span class="font-medium">{{ number_format($total) }}</span>
                        results
                    </p>
                </div>
    <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        @if($hasPreviousPage)
                            <button wire:click="previousPage" 
                                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Previous</span>
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        @endif

                        @for($i = max(1, $currentPage - 2); $i <= min($lastPage, $currentPage + 2); $i++)
                            <button wire:click="goToPage({{ $i }})" 
                                    class="relative inline-flex items-center px-4 py-2 border text-sm font-medium {{ $i === $currentPage ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50' }}">
                                {{ $i }}
                            </button>
                        @endfor

                        @if($hasNextPage)
                            <button wire:click="nextPage" 
                                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Next</span>
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        @endif
                    </nav>
                </div>
            </div>
        </div>
        @endif
    </div>
    @elseif(!$loading && count($transactions) === 0)
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No transactions found</h3>
        <p class="mt-1 text-sm text-gray-500">Try adjusting your filters or date range.</p>
    </div>
    @endif
</div>
