<?php

use Livewire\Volt\Component;
use App\Services\BusinessService;
use App\Services\Auth\AuthenticationService;
use App\Http\Integrations\TelconApiConnector;

new class extends Component {
    public $businesses = [];
    public $selectedBusiness = '';
    public $showBusinessDropdown = false;
    public $businessesLoaded = false;
    public $businessesError = '';
    
    public function mount() {
        // Initialize all properties with default values
        $this->businesses = [];
        $this->selectedBusiness = 'Technology Corp';
        $this->showBusinessDropdown = false;
        $this->businessesLoaded = false;
        $this->businessesError = '';
        
        $this->loadBusinessesFromSession();
    }
    
    public function loadBusinessesFromSession() {
        // Try to load businesses from session first
        $sessionBusinesses = session('user_businesses');
        $selectedBusiness = session('selected_business');
        
        if ($sessionBusinesses && is_array($sessionBusinesses)) {
            $this->businesses = $sessionBusinesses;
            $this->businessesLoaded = true;
            $this->businessesError = '';
            
            // Set selected business from session or first business
            if ($selectedBusiness && isset($selectedBusiness['name'])) {
                $this->selectedBusiness = $selectedBusiness['name'];
            } elseif (!empty($this->businesses)) {
                $this->selectedBusiness = $this->businesses[0]['name'] ?? 'Technology Corp';
            }
        } else {
            // Fallback to API call if no businesses in session
            $this->getAllBusinesses();
        }
    }
    
    public function getAllBusinesses() {
        $businessService = new BusinessService(new TelconApiConnector());
        $businessResponse = $businessService->getCurrentUserBusinesses();
        
        if ($businessResponse->isSuccessful()) {
            $this->businesses = $businessResponse->getBusinesses();
            $this->businessesLoaded = true;
            $this->businessesError = '';
            
            // Set the first business as selected if available
            if (!empty($this->businesses)) {
                $this->selectedBusiness = $this->businesses[0]['name'] ?? 'Technology Corp';
            }
        } else {
            $this->businesses = [];
            $this->businessesLoaded = false;
            $this->businessesError = $businessResponse->getMessage();
        }
    }
    
    public function switchBusiness($businessName) {
        $this->selectedBusiness = $businessName;
        $this->showBusinessDropdown = false;
        
        // Find the selected business data
        $selectedBusiness = collect($this->businesses)->firstWhere('name', $businessName);
        
        if ($selectedBusiness) {
            // Store the selected business in session for other components to use
            session(['selected_business' => $selectedBusiness]);
            
            // Use JavaScript to refresh the page
            $this->dispatch('business-switched');
        }
    }

    public function logout()
    {
        $authService = new AuthenticationService(new TelconApiConnector());
        $authService->logout();
        return redirect('/login');
    }
}; ?>

<div class="hidden lg:flex lg:flex-shrink-0 h-screen">
        <div class="flex flex-col w-64 h-full">
            <div class="flex flex-col h-full bg-white border-r border-gray-200">
                <div class="flex-1 flex flex-col pt-5 pb-4 overflow-y-auto h-full">
                    <div class="flex items-center flex-shrink-0 px-4">
                        <img src="{{ asset('logo/telcongh_main.png') }}" alt="TelconGH" class="h-24 w-auto">
                    </div>
                    <nav class="mt-5 flex-1 px-2 space-y-1">
                        <!-- Desktop Business Switcher -->
                        <div class="bg-gray-50 rounded-lg p-3 mb-4">
                            <h3 class="text-sm font-medium text-gray-700 mb-2">Current Business</h3>
                            <div class="relative">
                                <button wire:click="$toggle('showBusinessDropdown')" 
                                        class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                    <div class="flex flex-col text-left">
                                        <span class="font-medium">{{ $selectedBusiness ?? 'No Business' }}</span>
                                        @php
                                            $currentBusiness = !empty($businesses) ? collect($businesses)->firstWhere('name', $selectedBusiness) : null;
                                        @endphp
                                        @if($currentBusiness)
                                            <span class="text-xs text-gray-500">{{ $currentBusiness['business_code'] ?? 'N/A' }}</span>
                                        @endif
                                    </div>
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                
                                @if($showBusinessDropdown)
                                <div class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg">
                                <div class="py-1">
                                    @if(!empty($businesses) && is_array($businesses))
                                        @foreach($businesses as $business)
                                        <button wire:click="switchBusiness('{{ $business['name'] ?? 'Unknown' }}')" 
                                                class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-primary hover:text-white transition-colors">
                                            <div class="flex flex-col">
                                                <span class="font-medium">{{ $business['name'] ?? 'Unknown Business' }}</span>
                                                <span class="text-xs text-gray-500">{{ $business['business_code'] ?? 'N/A' }}</span>
                                            </div>
                                        </button>
                                        @endforeach
                                    @else
                                        <div class="px-3 py-2 text-sm text-gray-500">
                                            No businesses found
                                        </div>
                                    @endif
                                </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Desktop Navigation -->
                        <a href="{{ route('dashboard.overview') }}" 
                           class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('dashboard.overview') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            </svg>
                            Overview
                        </a>
                        <a href="{{ route('dashboard.products') }}" 
                           class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('dashboard.products') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            Products
                        </a>
                        <a href="{{ route('dashboard.portfolio') }}" 
                           class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('dashboard.portfolio') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                            Portfolio
                        </a>
                        <a href="{{ route('dashboard.orders') }}" 
                           class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('dashboard.orders') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            Orders
                        </a>
                        <a href="{{ route('dashboard.analytics') }}" 
                           class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('dashboard.analytics') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            Analytics
                        </a>
                        <a href="{{ route('dashboard.networks') }}" 
                           class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('dashboard.networks') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            Networks
                        </a>
                        <a href="{{ route('dashboard.settings') }}" 
                           class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('dashboard.settings') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Settings
                        </a>
                        <button wire:click="logout" 
                           class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors text-red-600 hover:bg-red-50">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            Logout
                        </button>
                    </nav>
                </div>
            </div>
        </div>
    </div>
