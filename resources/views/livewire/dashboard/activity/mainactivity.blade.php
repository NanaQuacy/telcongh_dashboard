<?php

use Livewire\Volt\Component;
use App\Services\NetworkService;
use App\Http\Integrations\TelconApiConnector;

new class extends Component {
    public $activeTab = null;
    public $networks = [];
    public $networksLoaded = false;
    public $selectedNetwork = null;
    public $networkServices = [];
    public $servicesLoaded = false;
    public $businessId = null;
    public function mount() {
        $this->businessId = session('selected_business')['id'];
        $this->loadNetworksData();
    }
    
    public function updatedBusinessId() {
        // Reload data when business changes
        $this->loadNetworksData();
    }
    
    public function refreshData() {
        // Method to refresh all data (can be called from UI)
        $this->businessId = session('selected_business')['id'];
        $this->loadNetworksData();
    }
    
    public function setActiveTab($tab) {
        $this->activeTab = $tab;
        
        // If tab is a network ID, set selected network
        if (is_numeric($tab)) {
            $this->selectedNetwork = collect($this->networks)->firstWhere('id', (int)$tab);
        } else {
            $this->selectedNetwork = null;
        }
        
        // Load data based on active tab
        if (is_numeric($tab)) {
            $this->loadNetworkData($tab);
        }
    }
    
    
    public function loadNetworksData() {
        $networkService = new NetworkService(new TelconApiConnector());
        $networkResponse = $networkService->getNetworksForCurrentUser();
        if ($networkResponse->isSuccessful()) {
            $this->networks = $networkResponse->getNetworks();
            $this->networksLoaded = true;
            
            // Set first network as active tab if no tab is selected
            if (empty($this->activeTab)) {
                if (!empty($this->networks)) {
                    $this->setActiveTab($this->networks[0]['id']);
                }
            }
        } else {
            $this->networks = [];
            $this->networksLoaded = false;
        }
    }
    
    public function loadNetworkData($networkId) {
        // Load specific network data
        $this->selectedNetwork = collect($this->networks)->firstWhere('id', (int)$networkId);
        
        // Load network services for the selected network
        $this->loadNetworkServices($this->businessId, (int)$networkId);
    }
    
    public function loadNetworkServices($businessId, $networkId = null) {
        $networkService = new NetworkService(new TelconApiConnector());
        $servicesResponse = $networkService->getServicesByNetwork((int)$businessId);
        
        if ($servicesResponse->isSuccessful()) {
            $allServices = $servicesResponse->getServices();
            
            // Filter services by network if networkId is provided
            if ($networkId) {
                $this->networkServices = collect($allServices)->filter(function($service) use ($networkId) {
                    return $service['network_service']['network']['id'] == $networkId;
                })->values()->toArray();
            } else {
                $this->networkServices = $allServices;
            }
            
            $this->servicesLoaded = true;
        } else {
            $this->networkServices = [];
            $this->servicesLoaded = false;
        }
    }
    public function chooseService($serviceId) {
        // Store the selected service in session
        session(['selected_service' => $serviceId]);
        
        // Get the stored value to verify
        $chosenService = session('selected_service');
        
        return redirect()->route('dashboard.perform-service');
    }
}; ?>

<div class="w-screen min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200 w-full">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="py-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Action Center</h1>
                        <p class="mt-1 text-sm text-gray-600">Manage your network operations and transactions</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button wire:click="refreshData" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
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

    <!-- Navigation Tabs -->
    <div class="w-full px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 w-full">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8 px-6 overflow-x-auto" aria-label="Tabs">
                  
                    <!-- Dynamic Network Tabs -->
                    @if($networksLoaded && !empty($networks))
                        @foreach($networks as $network)
                        <button wire:click="setActiveTab('{{ $network['id'] }}')" 
                                class="py-4 px-1 border-b-2 font-medium text-sm transition-colors whitespace-nowrap {{ $activeTab == $network['id'] ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                                style="border-bottom-color: {{ $activeTab == $network['id'] ? $network['color_code'] : 'transparent' }}">
                            <div class="flex items-center">
                                <div class="w-5 h-5 mr-2 rounded-full" style="background-color: {{ $network['color_code'] }}"></div>
                                {{ $network['name'] }}
                            </div>
                        </button>
                        @endforeach
                    @endif
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="p-6 w-full">
                @if(is_numeric($activeTab) && $selectedNetwork)
                    <!-- Network-Specific Content -->
                    <div class="space-y-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full mr-3" style="background-color: {{ $selectedNetwork['color_code'] }}"></div>
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-900">{{ $selectedNetwork['name'] }} Network</h2>
                                    <p class="text-sm text-gray-500">{{ count($networkServices) }} services available</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $selectedNetwork['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $selectedNetwork['is_active'] ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        
                        <!-- Network Services -->
                        @if($servicesLoaded && !empty($networkServices))
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($networkServices as $service)
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-sm transition-shadow">
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background-color: {{ $selectedNetwork['color_code'] }}">
                                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </div>
                                            <div class="ml-3">
                                                <h5 class="text-sm font-medium text-gray-900">{{ $service['network_service']['service']['name'] ?? 'Service' }}</h5>
                                            </div>
                                        </div>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $service['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $service['is_active'] ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                    
                                    @if(isset($service['network_service']['service']['description']) && $service['network_service']['service']['description'])
                                    <p class="text-xs text-gray-600 mb-3">{{ Str::limit($service['network_service']['service']['description'], 80) }}</p>
                                    @endif
                                    
                                    <!-- Simplified Pricing Information -->
                                    <div class="bg-gray-50 rounded-lg p-3 mb-3">
                                        <h6 class="text-xs font-medium text-gray-700 mb-2">Pricing</h6>
                                        <div class="grid grid-cols-2 gap-2 text-xs">
                                            <div class="flex justify-between">
                                                <span class="text-gray-500">Selling Price:</span>
                                                <span class="font-medium text-green-600">₵{{ number_format($service['selling_price'], 2) }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-500">Margin:</span>
                                                <span class="font-medium text-purple-600">{{ $service['profit_margin'] }}%</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex space-x-2">
                                        <button wire:click="chooseService('{{ $service['id'] }}')" class="flex-1 bg-primary text-white py-1 px-3 rounded text-xs hover:bg-primary-dark transition-colors">
                                            Choose Service
                                        </button>
                                        <button class="flex-1 bg-gray-100 text-gray-700 py-1 px-3 rounded text-xs hover:bg-gray-200 transition-colors">
                                            Edit Pricing
                                        </button>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <h4 class="mt-2 text-sm font-medium text-gray-900">No services found</h4>
                                <p class="mt-1 text-xs text-gray-500">No services are available for {{ $selectedNetwork['name'] }} network.</p>
                            </div>
                        @endif
                    </div>
                @else
                    <!-- No Network Selected -->
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No networks available</h3>
                        <p class="mt-1 text-sm text-gray-500">Please contact your administrator to set up networks.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
