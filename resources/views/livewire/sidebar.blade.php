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
            
            // Dispatch event to refresh the page
            $this->dispatch('business-switched');
            
            // Also dispatch a browser event for additional reliability
            $this->js('window.location.reload();');
        }
    }

    public function logout()
    {
        $authService = new AuthenticationService(new TelconApiConnector());
        $authService->logout();
        return redirect('/login');
    }
}; ?>

<div>
    <!-- Desktop Sidebar -->
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
                        <a href="{{ route('dashboard.index') }}" 
                           class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('dashboard.index') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            </svg>
                            Dashboard
                        </a>
                        <a href="{{ route('dashboard.networks') }}" 
                           class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('dashboard.networks') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                            </svg>
                            Network Services
                        </a>
                        <a href="{{ route('dashboard.inventory') }}" 
                           class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('dashboard.inventory') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <!-- Inventory icon: a simple box -->
                                <rect x="3" y="7" width="18" height="13" rx="2" stroke="currentColor" stroke-width="2" fill="none"/>
                                <path d="M16 3v4M8 3v4M3 11h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Inventory
                        </a>
                        <a href="{{ route('dashboard.transactions') }}" 
                           class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('dashboard.transactions') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                            Transactions
                        </a>
                        <a href="{{ route('dashboard.customers') }}" 
                           class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('dashboard.customers') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                            Customers
                        </a>
                        <a href="{{ route('dashboard.analytics') }}" 
                           class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('dashboard.analytics') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Finance
                        </a>
                        <a href="{{ route('dashboard.reports') }}" 
                           class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('dashboard.reports') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            Reports
                        </a>
                       
                        <a href="{{ route('dashboard.settings') }}" 
                           class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('dashboard.settings') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Settings
                        </a>
                        <a href="{{ route('dashboard.roles') }}" 
                           class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('dashboard.roles') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                            Roles & Permissions
                        </a>
                        <a href="{{ route('dashboard.users') }}" 
                           class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('dashboard.users') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                            Users
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

    <!-- Mobile Sidebar Overlay -->
    <div id="mobileSidebar" class="hidden fixed inset-0 z-50 lg:hidden">
        <div class="fixed inset-0 bg-gray-100 bg-opacity-75 backdrop-blur-sm" onclick="toggleMobileSidebar()"></div>
        <div class="relative flex-1 flex flex-col max-w-xs w-full bg-white bg-opacity-95 backdrop-blur-md shadow-2xl">
            <div class="absolute top-0 right-0 -mr-12 pt-2 z-50">
                <button onclick="toggleMobileSidebar()" class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="flex-1 h-screen pt-5 pb-4 overflow-y-auto">
                <div class="flex-shrink-0 flex items-center px-4">
                    <img src="{{ asset('logo/telcongh_main.png') }}" alt="TelconGH" class="h-12 w-auto">
                </div>
                
                <!-- Mobile Business Switcher -->
                <div class="mt-4 px-4">
                    <div class="bg-gray-50 rounded-lg p-3">
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
                </div>
                
                <nav class="mt-5 px-2 space-y-1 pb-8">
                    <!-- Mobile Navigation -->
                    <a href="{{ route('dashboard.index') }}" 
                       class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('dashboard.index') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                        </svg>
                        Dashboard
                    </a>
                    <a href="{{ route('dashboard.networks') }}" 
                       class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('dashboard.networks') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                        </svg>
                        Network Services
                    </a>
                    <a href="{{ route('dashboard.inventory') }}" 
                       class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('dashboard.inventory') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <!-- Inventory icon: a simple box -->
                            <rect x="3" y="7" width="18" height="13" rx="2" stroke="currentColor" stroke-width="2" fill="none"/>
                            <path d="M16 3v4M8 3v4M3 11h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Inventory
                    </a>
                    <a href="{{ route('dashboard.transactions') }}" 
                       class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('dashboard.transactions') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                        Transactions
                    </a>
                    <a href="{{ route('dashboard.customers') }}" 
                       class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('dashboard.customers') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        Customers
                    </a>
                    <a href="{{ route('dashboard.analytics') }}" 
                       class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('dashboard.analytics') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Finance
                    </a>
                    <a href="{{ route('dashboard.reports') }}" 
                       class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('dashboard.reports') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Reports
                    </a>
                    <a href="{{ route('dashboard.settings') }}" 
                       class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('dashboard.settings') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Settings
                    </a>
                    <a href="{{ route('dashboard.roles') }}" 
                       class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('dashboard.roles') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        Roles & Permissions
                    </a>
                    <a href="{{ route('dashboard.users') }}" 
                       class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('dashboard.users') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        Users
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

    <script>
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('mobileSidebar');
            sidebar.classList.toggle('hidden');
        }
    </script>
</div>