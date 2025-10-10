<?php

use Livewire\Volt\Component;
use App\Http\Integrations\TelconApiConnector;
use App\Http\Integrations\Requests\GetBusinessTransactionsRequest;
use App\Http\Integrations\Data\TransactionResponse;

new class extends Component {
    public $transactions = [];
    public $loading = true;
    public $error = null;
    public $businessId = 4; // Default business ID
    public $filters = [
        'status' => '',
        'payment_status' => '',
        'date_from' => '',
        'date_to' => '',
        'search' => '',
        'limit' => 20,
        'page' => 1
    ];
    public $selectedTransactionId = null;
    public $showCustomerModal = false;
    public $showPaymentModal = false;
    public $selectedCustomerData = null;
    public $selectedPaymentData = null;

    public $pagination = [];
    public $totalTransactions = 0;

    public function mount()
    {
        $this->loadTransactions();
    }

    public function loadTransactions()
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

            $request = new GetBusinessTransactionsRequest($this->businessId, $token);
            // Add filters to the request
            request()->merge($this->filters);
            
            $response = $connector->send($request);
          
            if ($response->successful()) {
                $data = $response->json();

               
                // Handle different response structures
                if (isset($data['data'])) {
                    $this->transactions = $data['data']['data'];
                  
                    $this->pagination = $data['pagination'] ?? [];
                    $this->totalTransactions = $data['total'] ?? count($this->transactions);
                } else {
                    $this->transactions = is_array($data) ? $data : [$data];
                    $this->totalTransactions = count($this->transactions);
                }
                
                \Log::info('Transactions loaded successfully', [
                    'count' => count($this->transactions),
                    'business_id' => $this->businessId
                ]);
            } else {
                $this->error = 'Failed to load transactions. Status: ' . $response->status();
                \Log::error('Failed to load transactions', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            $this->error = 'Error loading transactions: ' . $e->getMessage();
            \Log::error('Exception loading transactions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } finally {
            $this->loading = false;
        }
    }

    public function applyFilters()
    {
        $this->filters['page'] = 1; // Reset to first page
        $this->loadTransactions();
    }

    public function clearFilters()
    {
        $this->filters = [
            'status' => '',
            'payment_status' => '',
            'date_from' => '',
            'date_to' => '',
            'search' => '',
            'limit' => 20,
            'page' => 1
        ];
        $this->loadTransactions();
    }

    public function changePage($page)
    {
        $this->filters['page'] = $page;
        $this->loadTransactions();
    }

    public function getTransactionStatusBadgeClass($status)
    {
        return match($status) {
            'completed' => 'bg-green-100 text-green-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
            'in_progress' => 'bg-blue-100 text-blue-800',
            'cancelled' => 'bg-red-100 text-red-800',
            'refunded' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    public function getPaymentStatusBadgeClass($status)
    {
        return match($status) {
            'completed' => 'bg-green-100 text-green-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
            'partial' => 'bg-orange-100 text-orange-800',
            'overdue' => 'bg-red-100 text-red-800',
            'cancelled' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    public function formatCurrency($amount)
    {
        if (empty($amount) || $amount === null) {
            return 'GH₵ 0.00';
        }
        
        return 'GH₵ ' . number_format((float) $amount, 2);
    }

    public function formatDate($date)
    {
        if (empty($date) || $date === 'N/A' || $date === null) {
            return 'N/A';
        }
        
        try {
            return \Carbon\Carbon::parse($date)->format('M d, Y H:i');
        } catch (\Exception $e) {
            \Log::warning('Failed to parse date', [
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            return 'Invalid Date';
        }
    }

    public function getTransactionDate($transactionData)
    {
        // Try different possible date fields
        $dateFields = ['created_at', 'updated_at', 'transaction_date', 'date'];
        
        foreach ($dateFields as $field) {
            if (!empty($transactionData[$field]) && $transactionData[$field] !== 'N/A') {
                return $transactionData[$field];
            }
        }
        
        return null;
    }


    public function viewCustomerDetails($transactionId)
    {
        $this->selectedTransactionId = $transactionId;
        
        // Find the transaction data
        $transaction = collect($this->transactions)->firstWhere('id', $transactionId);
        
        if ($transaction && isset($transaction['customer_service_detail'])) {
            $this->selectedCustomerData = $transaction['customer_service_detail'];
            $this->showCustomerModal = true;
        }
    }

    public function viewPaymentDetails($transactionId)
    {
        $this->selectedTransactionId = $transactionId;
        
        // Find the transaction data
        $transaction = collect($this->transactions)->firstWhere('id', $transactionId);
        
        if ($transaction && isset($transaction['payment_detail'])) {
            $this->selectedPaymentData = $transaction['payment_detail'];
            $this->showPaymentModal = true;
        }
    }

    public function closeModals()
    {
        $this->showCustomerModal = false;
        $this->showPaymentModal = false;
        $this->selectedCustomerData = null;
        $this->selectedPaymentData = null;
        $this->selectedTransactionId = null;
    }
}; ?>

<div class="p-4 sm:p-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Transactions</h1>
        <p class="text-sm sm:text-base text-gray-600 mt-1">Manage and view all business transactions</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 sm:p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Filters</h3>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Status Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Transaction Status</label>
                <select wire:model.live="filters.status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="refunded">Refunded</option>
                </select>
            </div>

            <!-- Payment Status Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Status</label>
                <select wire:model.live="filters.payment_status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Payment Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="partial">Partial</option>
                    <option value="completed">Completed</option>
                    <option value="overdue">Overdue</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <!-- Date From -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                <input type="date" wire:model.live="filters.date_from" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <!-- Date To -->
<div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                <input type="date" wire:model.live="filters.date_to" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>

        <!-- Search and Actions -->
        <div class="flex flex-col sm:flex-row gap-4 mt-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" wire:model.live.debounce.300ms="filters.search" placeholder="Search by customer name, phone, or transaction ID..." class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            
            <div class="flex gap-2 items-end">
                <button wire:click="applyFilters" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Apply Filters
                </button>
                <button wire:click="clearFilters" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    Clear
                </button>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    @if($loading)
        <div class="flex justify-center items-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span class="ml-2 text-gray-600">Loading transactions...</span>
        </div>
    @endif

    <!-- Error State -->
    @if($error)
        <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Error</h3>
                    <div class="mt-2 text-sm text-red-700">{{ $error }}</div>
                </div>
            </div>
        </div>
    @endif

    <!-- Transactions Table -->
    @if(!$loading && !$error && count($transactions) > 0)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">
                    Transactions ({{ $totalTransactions }})
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction</th>
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($transactions as $transaction)
                            @php
                                // The transaction data is directly in the array, not nested
                                $transactionData = $transaction;
                                $paymentData = $transaction['payment_detail'] ?? null;
                                $customerData = $transaction['customer_service_detail'] ?? null;
                                $networkData = $transaction['network'] ?? null;
                                $serviceData = $transaction['service'] ?? null;
                                $businessData = $transaction['business'] ?? null;
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <!-- Transaction Info -->
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        #{{ $transactionData['id'] ?? 'N/A' }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $transactionData['transaction_notes'] ?? 'No notes' }}
                                    </div>
                                </td>

                                <!-- Customer Info -->
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                    @if($customerData)
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $customerData['full_name'] ?? 'N/A' }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $customerData['phone_number'] ?? 'N/A' }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $customerData['location'] ?? 'N/A' }}
                                        </div>
                                    @else
                                        <div class="text-sm text-gray-500">No customer data</div>
                                    @endif
                                </td>

                                <!-- Service Info -->
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                    @if($networkData && $serviceData)
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $networkData['name'] ?? 'N/A' }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $serviceData['name'] ?? 'N/A' }}
                                        </div>
                                    @else
                                        <div class="text-sm text-gray-500">No service data</div>
                                    @endif
                                </td>

                                <!-- Amount -->
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $this->formatCurrency($transactionData['selling_price'] ?? 0) }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        Cost: {{ $this->formatCurrency($transactionData['cost_price'] ?? 0) }}
                                    </div>
                                    @if(isset($transactionData['profit']))
                                        <div class="text-sm text-green-600">
                                            Profit: {{ $this->formatCurrency($transactionData['profit']) }}
                                        </div>
                                    @endif
                                </td>

                                <!-- Transaction Status -->
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $this->getTransactionStatusBadgeClass($transactionData['transaction_status'] ?? 'unknown') }}">
                                        {{ ucfirst($transactionData['transaction_status'] ?? 'Unknown') }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex flex-col space-y-1">
                                        @if($customerData)
                                            <button wire:click="viewCustomerDetails({{ $transactionData['id'] }})" 
                                                    class="text-indigo-600 hover:text-indigo-900 text-xs bg-indigo-50 hover:bg-indigo-100 px-2 py-1 rounded">
                                                View Customer
                                            </button>
                                        @endif
                                        @if($paymentData)
                                            <button wire:click="viewPaymentDetails({{ $transactionData['id'] }})" 
                                                    class="text-green-600 hover:text-green-900 text-xs bg-green-50 hover:bg-green-100 px-2 py-1 rounded">
                                                View Payment
                                            </button>
                                        @endif
                                    </div>
                                </td>

                                <!-- Date -->
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $this->formatDate($this->getTransactionDate($transactionData)) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if(isset($pagination['total_pages']) && $pagination['total_pages'] > 1)
            <div class="mt-6 flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing page {{ $pagination['current_page'] ?? 1 }} of {{ $pagination['total_pages'] ?? 1 }}
                </div>
                <div class="flex space-x-2">
                    @if(($pagination['current_page'] ?? 1) > 1)
                        <button wire:click="changePage({{ ($pagination['current_page'] ?? 1) - 1 }})" class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Previous
                        </button>
                    @endif
                    
                    @if(($pagination['current_page'] ?? 1) < ($pagination['total_pages'] ?? 1))
                        <button wire:click="changePage({{ ($pagination['current_page'] ?? 1) + 1 }})" class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Next
                        </button>
                    @endif
                </div>
            </div>
        @endif
    @endif

    <!-- Empty State -->
    @if(!$loading && !$error && count($transactions) === 0)
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No transactions found</h3>
            <p class="mt-1 text-sm text-gray-500">Try adjusting your filters or check back later.</p>
        </div>
    @endif

    <!-- Customer Details Modal -->
    @if($showCustomerModal && $selectedCustomerData)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click="closeModals">
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white" wire:click.stop>
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Customer Details</h3>
                        <button wire:click="closeModals" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Full Name</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $selectedCustomerData['full_name'] ?? 'N/A' }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $selectedCustomerData['phone_number'] ?? 'N/A' }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $selectedCustomerData['email'] ?? 'N/A' }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Location</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $selectedCustomerData['location'] ?? 'N/A' }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Next of Kin Name</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $selectedCustomerData['NOK_name'] ?? 'N/A' }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Next of Kin Phone</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $selectedCustomerData['NOK_phone'] ?? 'N/A' }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Alternate Phone</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $selectedCustomerData['Alternate_phone_number'] ?? 'N/A' }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">SIM Serial Number</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $selectedCustomerData['SIM_serial_number'] ?? 'N/A' }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $selectedCustomerData['Status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ ucfirst($selectedCustomerData['Status'] ?? 'Unknown') }}
                            </span>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Ticket Number</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $selectedCustomerData['Ticket_Number'] ?? 'N/A' }}</p>
                        </div>
                    </div>
                    
                    <!-- Activation Statuses -->
                    <div class="mt-6">
                        <h4 class="text-md font-medium text-gray-900 mb-3">Activation Statuses</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="text-center">
                                <div class="text-sm font-medium text-gray-700">MyMTN App</div>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $selectedCustomerData['MyMTNApp_Activation_Status'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $selectedCustomerData['MyMTNApp_Activation_Status'] ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <div class="text-center">
                                <div class="text-sm font-medium text-gray-700">MoMo App</div>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $selectedCustomerData['MomoApp_Activation_Status'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $selectedCustomerData['MomoApp_Activation_Status'] ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <div class="text-center">
                                <div class="text-sm font-medium text-gray-700">ADS</div>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $selectedCustomerData['ADS_Activation_Status'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $selectedCustomerData['ADS_Activation_Status'] ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <div class="text-center">
                                <div class="text-sm font-medium text-gray-700">RGT</div>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $selectedCustomerData['RGT_Activation_Status'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $selectedCustomerData['RGT_Activation_Status'] ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    @if($selectedCustomerData['Remarks'])
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">Remarks</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $selectedCustomerData['Remarks'] }}</p>
                        </div>
                    @endif
                    
                    @if($selectedCustomerData['Reason_for_Action'])
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">Reason for Action</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $selectedCustomerData['Reason_for_Action'] }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Payment Details Modal -->
    @if($showPaymentModal && $selectedPaymentData)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click="closeModals">
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white" wire:click.stop>
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Payment Details</h3>
                        <button wire:click="closeModals" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Payment ID</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $selectedPaymentData['id'] ?? 'N/A' }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Amount</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $this->formatCurrency($selectedPaymentData['amount'] ?? 0) }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Payment Method</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $selectedPaymentData['payment_method'] ?? 'N/A' }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $this->getPaymentStatusBadgeClass($selectedPaymentData['payment_status'] ?? 'unknown') }}">
                                {{ ucfirst($selectedPaymentData['payment_status'] ?? 'Unknown') }}
                            </span>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Reference</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $selectedPaymentData['reference'] ?? 'N/A' }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Provider</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $selectedPaymentData['provider'] ?? 'N/A' }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Created At</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $this->formatDate($selectedPaymentData['created_at'] ?? null) }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Updated At</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $this->formatDate($selectedPaymentData['updated_at'] ?? null) }}</p>
                        </div>
                    </div>
                    
                    @if($selectedPaymentData['description'])
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $selectedPaymentData['description'] }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
