<?php

use Livewire\Volt\Component;
use App\Services\NetworkService;
use App\Http\Integrations\TelconApiConnector;
use App\Http\Integrations\Requests\UpdateSimCardStatusRequest;

new class extends Component {
    public $batches = [];
    public $batchesLoaded = false;
    public $batchesError = '';
    public $businessId = null;
    
    // Networks for dropdown
    public $networks = [];
    public $networksLoaded = false;
    public $networksError = '';
    
    // Batch creation form
    public $showCreateBatchForm = false;
    public $batchName = '';
    public $batchDescription = '';
    public $boxBatchNumber = '';
    public $startingIccid = '';
    public $endingIccid = '';
    public $quantity = '';
    public $cost = '';
    public $selectedNetworkId = '';
    
    // Stock items creation
    public $showCreateItemsForm = false;
    public $selectedBatchId = null;
    public $serialNumbers = '';
    
    // View items
    public $showViewItemsForm = false;
    public $batchItems = [];
    public $itemsLoaded = false;
    public $itemsError = '';
    
    protected $rules = [
        'batchName' => 'required|string|min:2',
        'batchDescription' => 'required|string|min:5',
        'boxBatchNumber' => 'required|string',
        'startingIccid' => 'required|string',
        'endingIccid' => 'required|string',
        'quantity' => 'required|integer|min:1',
        'cost' => 'required|numeric|min:0',
        'selectedNetworkId' => 'required|string',
        'serialNumbers' => 'required|string|min:1',
    ];
    
    public function mount() {
        $selectedBusiness = session('selected_business');
        $this->businessId = is_array($selectedBusiness) ? ($selectedBusiness['id'] ?? null) : null;
        $this->loadBatches();
        $this->loadNetworks();
    }
    
    public function loadBatches() {
        if (!$this->businessId) {
            $this->batchesError = 'No business selected. Please select a business first.';
            $this->batchesLoaded = false;
            return;
        }
        
        try {
            $networkService = new NetworkService(new TelconApiConnector());
            $response = $networkService->getStockBatchesByBusiness($this->businessId);
            
            if ($response->isSuccessful()) {
                $this->batches = $response->getBatches();
                $this->batchesLoaded = true;
                $this->batchesError = '';
            } else {
                $this->batchesError = $response->getMessage();
                $this->batchesLoaded = false;
            }
        } catch (\Exception $e) {
            $this->batchesError = 'Failed to load stock batches: ' . $e->getMessage();
            $this->batchesLoaded = false;
        }
    }
    
    public function loadNetworks() {
        try {
            $networkService = new NetworkService(new TelconApiConnector());
            $response = $networkService->getAllNetworks();
            
            if ($response->isSuccessful()) {
                $this->networks = $response->getNetworks();
                $this->networksLoaded = true;
                $this->networksError = '';
            } else {
                $this->networksError = $response->getMessage();
                $this->networksLoaded = false;
            }
        } catch (\Exception $e) {
            $this->networksError = 'Failed to load networks: ' . $e->getMessage();
            $this->networksLoaded = false;
        }
    }
    
    public function showCreateBatch() {
        $this->showCreateBatchForm = true;
        $this->showCreateItemsForm = false;
        $this->resetBatchForm();
    }
    
    public function showCreateItems($batchId) {
        session()->put('selectedBatchId', $batchId);
        $this->selectedBatchId = $batchId;
        $this->showCreateItemsForm = true;
        $this->showCreateBatchForm = false;
        $this->showViewItemsForm = false;
        $this->resetItemsForm();
    }
    
    public function showViewItems($batchId) {
        session()->put('selectedBatchId', $batchId);
        $this->selectedBatchId = $batchId;
        $this->showViewItemsForm = true;
        $this->showCreateBatchForm = false;
        $this->showCreateItemsForm = false;
        $this->loadBatchItems();
    }
    
    public function loadBatchItems() {
        if (!$this->selectedBatchId) {
            $this->itemsError = 'No batch selected.';
            $this->itemsLoaded = false;
            return;
        }
        
        // Get the selected batch from the batches array
        $selectedBatch = collect($this->batches)->firstWhere('id', $this->selectedBatchId);
        
        if ($selectedBatch && isset($selectedBatch['stock_items'])) {
            $this->batchItems = $selectedBatch['stock_items'];
            $this->itemsLoaded = true;
            $this->itemsError = '';
        } else {
            $this->itemsError = 'No items found for this batch.';
            $this->itemsLoaded = false;
        }
    }
    
    public function createBatch() {
        $this->validate([
            'batchName' => 'required|string|min:2',
            'batchDescription' => 'required|string|min:5',
            'boxBatchNumber' => 'required|string',
            'startingIccid' => 'required|string',
            'endingIccid' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'cost' => 'required|numeric|min:0',
            'selectedNetworkId' => 'required|string',
        ]);
        
        if (!$this->businessId) {
            session()->flash('error', 'No business selected. Please select a business first.');
            return;
        }
        
        try {
            $networkService = new NetworkService(new TelconApiConnector());
            $response = $networkService->createStockBatch(
                $this->batchName,
                $this->batchDescription,
                $this->boxBatchNumber,
                $this->startingIccid,
                $this->endingIccid,
                (int) $this->quantity,
                (float) $this->cost,
                $this->businessId,
                $this->selectedNetworkId
            );
            
            if ($response->isSuccessful()) {
                $this->resetBatchForm();
                $this->showCreateBatchForm = false;
                $this->loadBatches();
                session()->flash('success', 'Stock batch created successfully!');
            } else {
                session()->flash('error', $response->getMessage());
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to create stock batch: ' . $e->getMessage());
        }
    }
    
    public function createStockItems() {
        $this->validate([
            'serialNumbers' => 'required|string|min:1',
        ]);
        
        if (!session()->get('selectedBatchId')) {
            session()->flash('error', 'No batch selected.');
            return;
        }
        
        // Pass the raw text input as string - API will handle parsing
        if (empty(trim($this->serialNumbers))) {
            session()->flash('error', 'Please enter valid serial numbers.');
            return;
        }
        
        try {
            $networkService = new NetworkService(new TelconApiConnector());
            $response = $networkService->createStockItems(session()->get('selectedBatchId'), $this->serialNumbers);
            
            if ($response->isSuccessful()) {
                $this->resetItemsForm();
                $this->showCreateItemsForm = false;
                session()->flash('success', "Successfully created {$response->getItemsCount()} stock items!");
            } else {
                session()->flash('error', $response->getMessage());
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to create stock items: ' . $e->getMessage());
        }
    }
    
    
    public function cancelBatchForm() {
        $this->showCreateBatchForm = false;
        $this->resetBatchForm();
    }
    
    public function cancelItemsForm() {
        $this->showCreateItemsForm = false;
        $this->resetItemsForm();
    }
    
    public function cancelViewItemsForm() {
        $this->showViewItemsForm = false;
        $this->batchItems = [];
        $this->itemsLoaded = false;
        $this->itemsError = '';
    }
    
    public function resetBatchForm() {
        $this->batchName = '';
        $this->batchDescription = '';
        $this->boxBatchNumber = '';
        $this->startingIccid = '';
        $this->endingIccid = '';
        $this->quantity = '';
        $this->cost = '';
        $this->selectedNetworkId = '';
        $this->resetErrorBag();
    }
    
    public function resetItemsForm() {
        $this->serialNumbers = '';
        $this->selectedBatchId = null;
        $this->resetErrorBag();
    }
    
    public function refreshData() {
        $this->loadBatches();
        $this->loadNetworks();
    }
    
    public function getSelectedBatch() {
        if (!$this->selectedBatchId) return null;
        return collect($this->batches)->firstWhere('id', $this->selectedBatchId);
    }

    public function toggleSimActive($itemId, $isActive) {
        try {
            $connector = new TelconApiConnector();
            $token = session('auth_token');
            
            if (!$token) {
                session()->flash('error', 'Authentication token not found. Please log in again.');
                return;
            }
            
            $request = new UpdateSimCardStatusRequest(
                token: $token,
                itemId: $itemId,
                status: 'is_active',
                value: $isActive === 'true'
            );
            
            $response = $connector->send($request);
            
            if ($response->successful()) {
                $status = $isActive === 'true' ? 'activated' : 'deactivated';
                session()->flash('success', "SIM card {$status} successfully!");
                
                // Reload the batch items to reflect the changes
                $this->loadBatchItems();
            } else {
                session()->flash('error', 'Failed to update SIM card status. Please try again.');
            }
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error updating SIM card status: ' . $e->getMessage());
        }
    }

    public function markSimSold($itemId) {
        try {
            $connector = new TelconApiConnector();
            $token = session('auth_token');
            
            if (!$token) {
                session()->flash('error', 'Authentication token not found. Please log in again.');
                return;
            }
            
            $request = new UpdateSimCardStatusRequest(
                token: $token,
                itemId: $itemId,
                status: 'is_sold',
                value: true
            );
            
            $response = $connector->send($request);
            
            if ($response->successful()) {
                session()->flash('success', 'SIM card marked as sold successfully!');
                
                // Reload the batch items to reflect the changes
                $this->loadBatchItems();
            } else {
                session()->flash('error', 'Failed to mark SIM card as sold. Please try again.');
            }
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error marking SIM card as sold: ' . $e->getMessage());
        }
    }
}; ?>

<div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-50">
    <!-- Header -->
    <div class="bg-white/80 backdrop-blur-sm shadow-lg border-b border-gray-200/50 sticky top-0 z-40">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="py-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center space-x-4">
                        <div class="p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl shadow-lg">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent">Inventory Management</h1>
                            <p class="mt-1 text-sm text-gray-600">Manage stock batches and SIM card inventory with ease</p>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button wire:click="showCreateBatch" 
                                class="group inline-flex items-center px-6 py-3 border border-transparent rounded-xl shadow-lg text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transform hover:scale-105 transition-all duration-200">
                            <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Create Batch
                        </button>
                        <button wire:click="refreshData" 
                                class="group inline-flex items-center px-6 py-3 border border-gray-300 rounded-xl shadow-lg text-sm font-semibold text-gray-700 bg-white/80 backdrop-blur-sm hover:bg-white hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transform hover:scale-105 transition-all duration-200">
                            <svg class="w-5 h-5 mr-2 group-hover:rotate-180 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Refresh
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        @if(!$businessId)
            <!-- No Business Selected -->
            <div class="bg-gradient-to-r from-amber-50 to-yellow-50 border border-amber-200 rounded-2xl p-8 shadow-lg">
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0">
                        <div class="p-3 bg-amber-100 rounded-full">
                            <svg class="h-8 w-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-amber-800 mb-2">No Business Selected</h3>
                        <p class="text-amber-700 mb-4">Please select a business from the sidebar to manage inventory.</p>
                        <div class="flex items-center text-sm text-amber-600">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Use the business switcher in the sidebar to get started
                        </div>
                    </div>
                </div>
            </div>
        @elseif($batchesError)
            <!-- Error State -->
            <div class="bg-gradient-to-r from-red-50 to-pink-50 border border-red-200 rounded-2xl p-8 shadow-lg">
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0">
                        <div class="p-3 bg-red-100 rounded-full">
                            <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-red-800 mb-2">Error Loading Batches</h3>
                        <p class="text-red-700 mb-4">{{ $batchesError }}</p>
                        <button wire:click="refreshData" 
                                class="inline-flex items-center px-4 py-2 bg-red-100 hover:bg-red-200 text-red-800 rounded-lg text-sm font-medium transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Try Again
                        </button>
                    </div>
                </div>
            </div>
        @elseif($batchesLoaded && count($batches) > 0)
            <!-- Batches Table -->
            <div class="bg-white/80 backdrop-blur-sm shadow-xl rounded-2xl overflow-hidden border border-gray-200/50">
                <div class="px-8 py-6 bg-gradient-to-r from-gray-50 to-blue-50 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">Stock Batches</h3>
                            <p class="mt-1 text-sm text-gray-600">Manage {{ count($batches) }} stock batches</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-gray-700">{{ count($batches) }} batches</span>
                        </div>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200/50">
                        <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                            <tr>
                                <th class="px-8 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Batch Info</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Network</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Box Details</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">ICCID Range</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Quantity</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Stock Count</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Cost</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white/50 divide-y divide-gray-200/30">
                            @foreach($batches as $batch)
                                <tr class="hover:bg-gradient-to-r hover:from-blue-50/50 hover:to-indigo-50/50 transition-all duration-200 group">
                                    <td class="px-8 py-6 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-12 h-12 rounded-xl flex items-center justify-center mr-4 bg-gradient-to-r from-blue-100 to-indigo-100 group-hover:from-blue-200 group-hover:to-indigo-200 transition-all duration-200">
                                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-sm font-semibold text-gray-900 group-hover:text-blue-900 transition-colors duration-200">{{ $batch['name'] ?? 'Batch' }}</div>
                                                <div class="text-sm text-gray-500 max-w-xs truncate">{{ $batch['description'] ?? 'No description' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-6 whitespace-nowrap">
                                        @if(isset($batch['network']) && $batch['network'])
                                            <div class="flex items-center">
                                                <div class="w-3 h-3 rounded-full mr-2" style="background-color: {{ $batch['network']['color_code'] ?? '#6B7280' }}"></div>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-white border" style="background-color: {{ $batch['network']['color_code'] ?? '#6B7280' }}">
                                                    {{ $batch['network']['name'] ?? 'Unknown' }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">
                                                No Network
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-6 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $batch['box_batch_number'] ?? 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-6 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $batch['starting_iccid'] ?? 'N/A' }}</div>
                                        <div class="text-sm text-gray-500">to {{ $batch['ending_iccid'] ?? 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-6 whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gradient-to-r from-green-100 to-emerald-100 text-green-800 border border-green-200">
                                            {{ $batch['quantity'] ?? 0 }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-6 whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gradient-to-r from-blue-100 to-cyan-100 text-blue-800 border border-blue-200">
                                            {{ $batch['stock_items_count'] ?? 0 }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-6 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900">₵{{ number_format($batch['cost'] ?? 0, 2) }}</div>
                                    </td>
                                    <td class="px-6 py-6 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-3">
                                            <button wire:click="showCreateItems({{ $batch['id'] }})" 
                                                    class="inline-flex items-center px-3 py-1.5 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-all duration-200 font-medium">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                </svg>
                                                Add Items
                                            </button>
                                            @if(($batch['stock_items_count'] ?? 0) > 0)
                                                <button wire:click="showViewItems({{ $batch['id'] }})" 
                                                        class="inline-flex items-center px-3 py-1.5 text-green-600 hover:text-green-800 hover:bg-green-50 rounded-lg transition-all duration-200 font-medium">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                    View Items
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif($batchesLoaded && count($batches) === 0)
            <!-- Empty State -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-200/50 p-12">
                <div class="text-center">
                    <div class="mx-auto w-24 h-24 bg-gradient-to-r from-blue-100 to-indigo-100 rounded-full flex items-center justify-center mb-6">
                        <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">No Stock Batches</h3>
                    <p class="text-gray-600 mb-8 max-w-md mx-auto">Get started by creating your first stock batch to manage your SIM card inventory.</p>
                    <button wire:click="showCreateBatch" 
                            class="inline-flex items-center px-8 py-4 border border-transparent shadow-lg text-lg font-semibold rounded-xl text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 transform hover:scale-105 transition-all duration-200">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Create First Batch
                    </button>
                </div>
            </div>
        @else
            <!-- Loading State -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-200/50 p-12">
                <div class="flex items-center justify-center">
                    <div class="text-center">
                        <div class="relative">
                            <div class="w-20 h-20 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mx-auto mb-6"></div>
                            <div class="absolute inset-0 w-20 h-20 border-4 border-transparent border-t-indigo-400 rounded-full animate-spin mx-auto" style="animation-delay: 0.5s; animation-duration: 1.5s;"></div>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Loading Batches</h3>
                        <p class="text-gray-600">Please wait while we fetch your stock batches...</p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="fixed top-6 right-6 z-50 animate-slide-in-right">
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 text-green-800 px-6 py-4 rounded-2xl shadow-2xl backdrop-blur-sm max-w-md">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-semibold">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="fixed top-6 right-6 z-50 animate-slide-in-right">
            <div class="bg-gradient-to-r from-red-50 to-pink-50 border border-red-200 text-red-800 px-6 py-4 rounded-2xl shadow-2xl backdrop-blur-sm max-w-md">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-semibold">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Create Batch Modal -->
    @if($showCreateBatchForm)
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm overflow-y-auto h-full w-full z-50 animate-fade-in" wire:click="cancelBatchForm">
            <div class="relative top-10 mx-auto p-6 border w-full max-w-3xl shadow-2xl rounded-2xl bg-white/95 backdrop-blur-sm" wire:click.stop>
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-gradient-to-r from-blue-100 to-indigo-100 rounded-lg">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900">Create Stock Batch</h3>
                        </div>
                        <button wire:click="cancelBatchForm" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <form wire:submit.prevent="createBatch">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="md:col-span-2">
                                <label for="batch_name" class="block text-sm font-semibold text-gray-700 mb-2">Batch Name</label>
                                <input type="text" id="batch_name" wire:model="batchName" 
                                       class="mt-1 p-4 block w-full border-2 border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-all duration-200 hover:border-gray-300"
                                       placeholder="Enter batch name" required>
                                @error('batchName') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <div class="md:col-span-2">
                                <label for="batch_description" class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                                <textarea id="batch_description" wire:model="batchDescription" rows="3"
                                          class="mt-1 p-4 block w-full border-2 border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-all duration-200 hover:border-gray-300 resize-none"
                                          placeholder="Enter batch description" required></textarea>
                                @error('batchDescription') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label for="box_batch_number" class="block text-sm font-semibold text-gray-700 mb-2">Box Batch Number</label>
                                <input type="text" id="box_batch_number" wire:model="boxBatchNumber" 
                                       class="mt-1 p-4 block w-full border-2 border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-all duration-200 hover:border-gray-300"
                                       placeholder="Enter box batch number" required>
                                @error('boxBatchNumber') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label for="quantity" class="block text-sm font-semibold text-gray-700 mb-2">Quantity</label>
                                <input type="number" id="quantity" wire:model="quantity" min="1"
                                       class="mt-1 p-4 block w-full border-2 border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-all duration-200 hover:border-gray-300"
                                       placeholder="Enter quantity" required>
                                @error('quantity') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label for="starting_iccid" class="block text-sm font-semibold text-gray-700 mb-2">Starting ICCID</label>
                                <input type="text" id="starting_iccid" wire:model="startingIccid" 
                                       class="mt-1 p-4 block w-full border-2 border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-all duration-200 hover:border-gray-300"
                                       placeholder="Enter starting ICCID" required>
                                @error('startingIccid') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label for="ending_iccid" class="block text-sm font-semibold text-gray-700 mb-2">Ending ICCID</label>
                                <input type="text" id="ending_iccid" wire:model="endingIccid" 
                                       class="mt-1 p-4 block w-full border-2 border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-all duration-200 hover:border-gray-300"
                                       placeholder="Enter ending ICCID" required>
                                @error('endingIccid') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label for="cost" class="block text-sm font-semibold text-gray-700 mb-2">Cost (₵)</label>
                                <input type="number" step="0.01" min="0" id="cost" wire:model="cost" 
                                       class="mt-1 p-4 block w-full border-2 border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-all duration-200 hover:border-gray-300"
                                       placeholder="0.00" required>
                                @error('cost') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <div class="md:col-span-2">
                                <label for="selected_network_id" class="block text-sm font-semibold text-gray-700 mb-2">Network</label>
                                <select id="selected_network_id" wire:model="selectedNetworkId" 
                                        class="mt-1 p-4 block w-full border-2 border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-all duration-200 hover:border-gray-300" required>
                                    <option value="">Select a network</option>
                                    @if($networksLoaded && count($networks) > 0)
                                        @foreach($networks as $network)
                                            <option value="{{ $network['id'] ?? $network['network_id'] ?? '' }}">
                                                {{ $network['name'] ?? $network['network_name'] ?? 'Unknown Network' }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('selectedNetworkId') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                                @if($networksError)
                                    <span class="text-red-500 text-sm mt-1 block">{{ $networksError }}</span>
                                @endif
                            </div>
                        </div>
                        
                        <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                            <button type="button" wire:click="cancelBatchForm" 
                                    class="px-6 py-3 border-2 border-gray-300 rounded-xl text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:border-gray-400 transition-all duration-200">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="px-8 py-3 border border-transparent rounded-xl shadow-lg text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 transform hover:scale-105 transition-all duration-200">
                                <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Create Batch
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Create Stock Items Modal -->
    @if($showCreateItemsForm)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click="cancelItemsForm">
            <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white" wire:click.stop>
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Add Stock Items</h3>
                        <button wire:click="cancelItemsForm" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    @if($this->getSelectedBatch())
                        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                            <h4 class="font-medium text-gray-900">{{ $this->getSelectedBatch()['name'] ?? 'Batch' }}</h4>
                            <p class="text-sm text-gray-600">{{ $this->getSelectedBatch()['description'] ?? 'No description' }}</p>
                            <p class="text-sm text-gray-500 mt-1">Quantity: {{ $this->getSelectedBatch()['quantity'] ?? 0 }} | Cost: ₵{{ number_format($this->getSelectedBatch()['cost'] ?? 0, 2) }}</p>
                        </div>
                    @endif
                    
                    <form wire:submit.prevent="createStockItems">
<div>
                            <label for="serial_numbers" class="block text-sm font-medium text-gray-700 mb-2">Serial Numbers</label>
                            <p class="text-sm text-gray-500 mb-3">Enter serial numbers separated by commas or on new lines. You can add 100-200 or any quantity.</p>
                            <textarea id="serial_numbers" wire:model="serialNumbers" rows="10"
                                      class="block w-full p-2 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm font-mono"
                                      placeholder="Enter serial numbers here...&#10;Example:&#10;12345678901234567890&#10;12345678901234567891&#10;12345678901234567892&#10;&#10;Or separated by commas:&#10;12345678901234567890, 12345678901234567891, 12345678901234567892" required></textarea>
                            @error('serialNumbers') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" wire:click="cancelItemsForm" 
                                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                                Create Stock Items
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- View Stock Items Modal -->
    @if($showViewItemsForm)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click="cancelViewItemsForm">
            <div class="relative top-10 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white" wire:click.stop>
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Stock Items</h3>
                        <button wire:click="cancelViewItemsForm" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    @if($this->getSelectedBatch())
                        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                            <h4 class="font-medium text-gray-900">{{ $this->getSelectedBatch()['name'] ?? 'Batch' }}</h4>
                            <p class="text-sm text-gray-600">{{ $this->getSelectedBatch()['description'] ?? 'No description' }}</p>
                            <p class="text-sm text-gray-500 mt-1">
                                Total Items: {{ $this->getSelectedBatch()['stock_items_count'] ?? 0 }} | 
                                Cost: ₵{{ number_format($this->getSelectedBatch()['cost'] ?? 0, 2) }}
                            </p>
                        </div>
                    @endif
                    
                    @if($itemsError)
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">Error</h3>
                                    <div class="mt-2 text-sm text-red-700">
                                        <p>{{ $itemsError }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif($itemsLoaded && count($batchItems) > 0)
                        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h4 class="text-md font-medium text-gray-900">Stock Items ({{ count($batchItems) }})</h4>
                            </div>
                            
                            <div class="overflow-x-auto max-h-96">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50 sticky top-0">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Serial Number</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Active Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sold Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($batchItems as $item)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-mono text-gray-900">{{ $item['serial_number'] ?? 'N/A' }}</div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    @if($item['is_active'] ?? false)
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            Active
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                            Inactive
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    @if($item['is_sold'] ?? false)
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                            Sold
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                            Available
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <div class="flex space-x-2">
                                                        @if(!($item['is_sold'] ?? false))
                                                            <button wire:click="toggleSimActive({{ $item['id'] }}, {{ $item['is_active'] ? 'false' : 'true' }})" 
                                                                    class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md transition-colors duration-200 {{ $item['is_active'] ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200' }}">
                                                                {{ $item['is_active'] ? 'Deactivate' : 'Activate' }}
                                                            </button>
                                                        @endif
                                                        @if(!($item['is_sold'] ?? false) && ($item['is_active'] ?? false))
                                                            <button wire:click="markSimSold({{ $item['id'] }})" 
                                                                    class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md bg-orange-100 text-orange-700 hover:bg-orange-200 transition-colors duration-200">
                                                                Mark Sold
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ isset($item['created_at']) ? \Carbon\Carbon::parse($item['created_at'])->format('M d, Y H:i') : 'N/A' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @elseif($itemsLoaded && count($batchItems) === 0)
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No Stock Items</h3>
                            <p class="mt-1 text-sm text-gray-500">This batch doesn't have any stock items yet.</p>
                        </div>
                    @else
                        <div class="flex items-center justify-center py-8">
                            <div class="text-center">
                                <div class="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mx-auto mb-4"></div>
                                <p class="text-sm text-gray-500">Loading items...</p>
                            </div>
                        </div>
                    @endif
                    
                    <div class="flex justify-end mt-6">
                        <button wire:click="cancelViewItemsForm" 
                                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
    
<style>
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slide-in-right {
    from {
        opacity: 0;
        transform: translateX(100%);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}

.animate-slide-in-right {
    animation: slide-in-right 0.4s ease-out;
}

/* Custom scrollbar */
.overflow-x-auto::-webkit-scrollbar {
    height: 8px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Smooth transitions for all interactive elements */
* {
    transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}
</style>

</div>
