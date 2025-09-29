<?php

use Livewire\Volt\Component;
use App\Services\NetworkService;
use App\Http\Integrations\TelconApiConnector;

new class extends Component {
    public $services = [];
    public $servicesLoaded = false;
    public $servicesError = '';
    public $businessId = null;
    public $pricingData = [];
    public $editingPricing = null;
    public $showPricingForm = false;
    
    // Form fields
    public $selectedServiceId = null;
    public $costPrice = '';
    public $sellingPrice = '';
    
    protected $rules = [
        'selectedServiceId' => 'required|integer',
        'costPrice' => 'required|numeric|min:0',
        'sellingPrice' => 'required|numeric|min:0',
    ];
    
    public function mount() {
        $selectedBusiness = session('selected_business');
        $this->businessId = is_array($selectedBusiness) ? ($selectedBusiness['id'] ?? null) : null;
        $this->loadActiveServices();
    }
    
    public function loadActiveServices() {
        try {
            $networkService = new NetworkService(new TelconApiConnector());
            $response = $networkService->getActiveNetworkServices();
            
            if ($response->isSuccessful()) {
                $this->services = $response->getServices();
                $this->servicesLoaded = true;
                $this->servicesError = '';
                
                // Load existing pricing for each service
                $this->loadExistingPricing();
            } else {
                $this->servicesError = $response->getMessage();
                $this->servicesLoaded = false;
            }
        } catch (\Exception $e) {
            $this->servicesError = 'Failed to load active network services: ' . $e->getMessage();
            $this->servicesLoaded = false;
        }
    }
    
    public function loadExistingPricing() {
        // This would load existing pricing data for the current business
        // For now, we'll initialize empty pricing data
        foreach ($this->services as $service) {
            $this->pricingData[$service['id']] = [
                'cost_price' => null,
                'selling_price' => null,
                'has_pricing' => false
            ];
        }
    }
    
    public function editPricing($serviceId) {
        $this->selectedServiceId = $serviceId;
        $service = collect($this->services)->firstWhere('id', $serviceId);
        
        if ($service) {
            $this->costPrice = $this->pricingData[$serviceId]['cost_price'] ?? '';
            $this->sellingPrice = $this->pricingData[$serviceId]['selling_price'] ?? '';
            $this->editingPricing = $serviceId;
            $this->showPricingForm = true;
        }
    }
    
    public function savePricing() {
        $this->validate();
        
        if (!$this->businessId) {
            session()->flash('error', 'No business selected. Please select a business first.');
            return;
        }
      
        try {
            $networkService = new NetworkService(new TelconApiConnector());
            $response = $networkService->createNetworkServicePricing(
                $this->selectedServiceId,
                $this->businessId,
                (float) $this->costPrice,
                (float) $this->sellingPrice
            );
            
            if ($response->isSuccessful()) {
                // Update local pricing data
                $this->pricingData[$this->selectedServiceId] = [
                    'cost_price' => (float) $this->costPrice,
                    'selling_price' => (float) $this->sellingPrice,
                    'has_pricing' => true
                ];
                
                $this->resetForm();
                session()->flash('success', 'Pricing saved successfully!');
            } else {
                session()->flash('error', $response->getMessage());
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to save pricing: ' . $e->getMessage());
        }
    }
    
    public function cancelEdit() {
        $this->resetForm();
    }
    
    public function resetForm() {
        $this->selectedServiceId = null;
        $this->costPrice = '';
        $this->sellingPrice = '';
        $this->editingPricing = null;
        $this->showPricingForm = false;
        $this->resetErrorBag();
    }
    
    public function refreshData() {
        $this->loadActiveServices();
    }
}; ?>

<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="py-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Manage Service Pricing</h1>
                        <p class="mt-1 text-sm text-gray-500">Set pricing for network services in your business</p>
                    </div>
                    <div class="flex space-x-3">
                        <button wire:click="refreshData" 
                                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if(!$businessId)
            <!-- No Business Selected -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">No Business Selected</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>Please select a business from the sidebar to manage service pricing.</p>
                        </div>
                    </div>
                </div>
            </div>
        @elseif($servicesError)
            <!-- Error State -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Error Loading Services</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <p>{{ $servicesError }}</p>
                        </div>
                        <div class="mt-4">
                            <button wire:click="refreshData" 
                                    class="bg-red-100 px-3 py-2 rounded-md text-sm font-medium text-red-800 hover:bg-red-200">
                                Try Again
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @elseif($servicesLoaded && count($services) > 0)
            <!-- Services Table -->
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Active Network Services</h3>
                    <p class="mt-1 text-sm text-gray-500">Manage pricing for {{ count($services) }} active services</p>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Network</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Selling Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Profit Margin</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($services as $service)
                                @php
                                    $pricing = $pricingData[$service['id']] ?? ['cost_price' => null, 'selling_price' => null, 'has_pricing' => false];
                                    $profitMargin = null;
                                    if ($pricing['cost_price'] && $pricing['selling_price']) {
                                        $profitMargin = (($pricing['selling_price'] - $pricing['cost_price']) / $pricing['cost_price']) * 100;
                                    }
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 rounded-lg flex items-center justify-center mr-3" 
                                                 style="background-color: {{ $service['network']['color_code'] ?? '#6B7280' }}">
                                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">{{ $service['service']['name'] ?? 'Service' }}</div>
                                                <div class="text-sm text-gray-500">ID: {{ $service['id'] }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $service['network']['name'] ?? 'Network' }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 max-w-xs truncate">
                                            {{ $service['service']['description'] ?? 'No description available' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($pricing['cost_price'])
                                            <span class="text-sm font-medium text-gray-900">₵{{ number_format($pricing['cost_price'], 2) }}</span>
                                        @else
                                            <span class="text-sm text-gray-400">Not set</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($pricing['selling_price'])
                                            <span class="text-sm font-medium text-green-600">₵{{ number_format($pricing['selling_price'], 2) }}</span>
                                        @else
                                            <span class="text-sm text-gray-400">Not set</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($profitMargin !== null)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $profitMargin > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ number_format($profitMargin, 1) }}%
                                            </span>
                                        @else
                                            <span class="text-sm text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button wire:click="editPricing({{ $service['id'] }})" 
                                                class="text-blue-600 hover:text-blue-900">
                                            {{ $pricing['has_pricing'] ? 'Edit' : 'Set Pricing' }}
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif($servicesLoaded && count($services) === 0)
            <!-- Empty State -->
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No Active Services</h3>
                <p class="mt-1 text-sm text-gray-500">There are no active network services available for pricing.</p>
                <div class="mt-6">
                    <button wire:click="refreshData" 
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        Refresh Data
                    </button>
                </div>
            </div>
        @else
            <!-- Loading State -->
            <div class="bg-white rounded-lg shadow-sm p-8">
                <div class="flex items-center justify-center">
                    <div class="text-center">
                        <div class="w-16 h-16 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mx-auto mb-4"></div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Loading Services</h3>
                        <p class="text-sm text-gray-500">Please wait while we fetch the active network services...</p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Pricing Form Modal -->
    @if($showPricingForm)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click="cancelEdit">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" wire:click.stop>
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Set Service Pricing</h3>
                        <button wire:click="cancelEdit" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    @if($selectedServiceId)
                        @php
                            $selectedService = collect($services)->firstWhere('id', $selectedServiceId);
                        @endphp
                        @if($selectedService)
                            <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                                <h4 class="font-medium text-gray-900">{{ $selectedService['service']['name'] ?? 'Service' }}</h4>
                                <p class="text-sm text-gray-600">{{ $selectedService['network']['name'] ?? 'Network' }} Network</p>
                            </div>
                        @endif
                    @endif
                    
                    <form wire:submit.prevent="savePricing">
                        <div class="space-y-4">
                            <div>
                                <label for="cost_price" class="block text-sm font-medium text-gray-700">Cost Price (₵)</label>
                                <input type="number" step="0.01" min="0" id="cost_price" wire:model="costPrice" 
                                       class="mt-1 block p-2 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                       placeholder="0.00" required>
                                @error('costPrice') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

<div>
                                <label for="selling_price" class="block text-sm font-medium text-gray-700">Selling Price (₵)</label>
                                <input type="number" step="0.01" min="0" id="selling_price" wire:model="sellingPrice" 
                                       class="mt-1 block p-2 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                       placeholder="0.00" required>
                                @error('sellingPrice') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            
                            @if($costPrice && $sellingPrice)
                                @php
                                    $profit = (float) $sellingPrice - (float) $costPrice;
                                    $profitMargin = $costPrice > 0 ? ($profit / (float) $costPrice) * 100 : 0;
                                @endphp
                                <div class="p-3 bg-blue-50 rounded-lg">
                                    <div class="text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Profit:</span>
                                            <span class="font-medium {{ $profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                ₵{{ number_format($profit, 2) }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Margin:</span>
                                            <span class="font-medium {{ $profitMargin >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                {{ number_format($profitMargin, 1) }}%
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        
                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" wire:click="cancelEdit" 
                                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                                Save Pricing
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="fixed top-4 right-4 z-50">
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded shadow-lg">
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="fixed top-4 right-4 z-50">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded shadow-lg">
                {{ session('error') }}
            </div>
        </div>
    @endif
</div>
