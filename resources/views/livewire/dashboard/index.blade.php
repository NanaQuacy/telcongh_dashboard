<?php

use Livewire\Volt\Component;
use App\Services\Auth\CheckAuthService;
use App\Services\Auth\AuthenticationService;
use App\Http\Integrations\TelconApiConnector;
use App\Services\BusinessService;
new class extends Component
{
    public $activeTab = 'overview';
    public $selectedBusiness = 'Technology Corp';
    public $showBusinessDropdown = false;
    public $showMobileSidebar = false;
    public $showUserDropdown = false;
    public $businesses = [];
    public $businessesLoaded = false;
    public $businessesError = '';
    public $currentBusiness = null;
    public $userName = null;
    public $userPermissions = [];
    public function mount(){
        $this->userPermissions = session('permissions', []);
            dd($this->userPermissions);
        $checkAuthService = new CheckAuthService();
        if(!$checkAuthService->checkAuth()){
            return redirect('/login');
        }else{
            // Fetch all permissions from the session and store in a property for Livewire/Blade components to use
            
            $this->getallbusinesses();
            // Set the first business as selected if available
            if (!empty($this->businesses)) {
                $this->selectedBusiness = $this->businesses[0]['name'] ?? 'Technology Corp';
            }
            $this->userName = $this->businesses[0]['user']['name'] ?? 'Technology Corp';
        }
    }
    public function getallbusinesses()
    {
        $businessService = new BusinessService(new TelconApiConnector());
        $businessResponse = $businessService->getCurrentUserBusinesses();
        
        if ($businessResponse->isSuccessful()) {
            // Convert BusinessResponse to array for Livewire compatibility
            $this->businesses = $businessResponse->getBusinesses();
            $this->businessesLoaded = true;
            $this->businessesError = '';
        } else {
            $this->businesses = [];
            $this->businessesLoaded = false;
            $this->businessesError = $businessResponse->getMessage();
        }
        
        return $businessResponse;
    }
    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
        // Close mobile sidebar when navigating on mobile
        $this->showMobileSidebar = false;
    }

    public function switchBusiness($businessName)
    {
        $this->selectedBusiness = $businessName;
        $this->showBusinessDropdown = false;
        
        // Find the selected business data
        $selectedBusiness = collect($this->businesses)->firstWhere('name', $businessName);
        
        if ($selectedBusiness) {
            // Store the selected business in session for other components to use
            session(['selected_business' => $selectedBusiness]);
            
            // You can add more business switching logic here
            // For example, refresh data, update UI, etc.
        }
    }

    public function logout()
    {
        $authService = new AuthenticationService(new TelconApiConnector());
        $authService->logout();
        return redirect('/login');
    }
};
?>

<div class="flex h-screen bg-gray-50">
    <!-- Mobile Sidebar Overlay -->
    @if($showMobileSidebar)
    <div class="fixed inset-0 z-40 lg:hidden">
        <div class="fixed inset-0 bg-gray-600 bg-opacity-75" wire:click="$set('showMobileSidebar', false)"></div>
        <div class="relative flex-1 flex flex-col max-w-xs w-full bg-white">
            <div class="absolute top-0 right-0 -mr-12 pt-2">
                <button wire:click="$set('showMobileSidebar', false)" class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="flex-1 h-0 pt-5 pb-4 overflow-y-auto">
                <div class="flex-shrink-0 flex items-center px-4">
                    <img src="{{ asset('logo/telcongh_main.png') }}" alt="TelconGH" class="h-12 w-auto">
                </div>
                <nav class="mt-5 px-2 space-y-1">
                    <!-- Mobile Business Switcher -->
                    <div class="bg-gray-50 rounded-lg p-3 mb-4">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Current Business</h3>
                        <div class="relative">
                            <button wire:click="$toggle('showBusinessDropdown')" 
                                    class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                <div class="flex flex-col text-left">
                                    <span class="font-medium">{{ $selectedBusiness }}</span>
                                    @php
                                        $currentBusiness = collect($businesses)->firstWhere('name', $selectedBusiness);
                                    @endphp
                                    @if($currentBusiness)
                                        <span class="text-xs text-gray-500">{{ $currentBusiness['business_code'] }}</span>
                                    @endif
                                </div>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            
                            @if($showBusinessDropdown)
                            <div class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg">
                                <div class="py-1">
                                    @if(!empty($businesses))
                                        @foreach($businesses as $business)
                                        <button wire:click="switchBusiness('{{ $business['name'] }}')" 
                                                class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-primary hover:text-white transition-colors">
                                            <div class="flex flex-col">
                                                <span class="font-medium">{{ $business['name'] }}</span>
                                                <span class="text-xs text-gray-500">{{ $business['business_code'] }}</span>
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
                    
                    <!-- Mobile Navigation -->
                    <button wire:click="setActiveTab('overview')" 
                            class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ $activeTab === 'overview' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                        </svg>
                        Overview
                    </button>
                    <button wire:click="setActiveTab('stocks')" 
                            class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ $activeTab === 'stocks' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Products
                    </button>
                    <button wire:click="setActiveTab('portfolio')" 
                            class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ $activeTab === 'portfolio' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                        Portfolio
                    </button>
                    <button wire:click="setActiveTab('orders')" 
                            class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ $activeTab === 'orders' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        Orders
                    </button>
                    <button wire:click="setActiveTab('analytics')" 
                            class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ $activeTab === 'analytics' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Analytics
                    </button>
                    <button wire:click="setActiveTab('settings')" 
                            class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ $activeTab === 'settings' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Settings
                    </button>
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
    @endif

    <!-- Desktop Sidebar -->
   @include('livewire.dashboard.include.sidebar')
    
    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="px-4 sm:px-6 py-4">
                <div class="flex items-center justify-between">
                    <!-- Mobile menu button -->
                    <div class="flex items-center">
                        <button wire:click="$set('showMobileSidebar', true)" class="lg:hidden -ml-0.5 -mt-0.5 h-12 w-12 inline-flex items-center justify-center rounded-md text-gray-500 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <div class="ml-4 lg:ml-0">
                            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Dashboard</h1>
                            <p class="text-xs sm:text-sm text-gray-600 hidden sm:block">Welcome back! <span class="font-medium">{{ $userName }}</span> Here's what's happening with your business.</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-2 sm:space-x-4">
                        <!-- Search - Hidden on mobile -->
                        <div class="relative hidden sm:block">
                            <input type="text" placeholder="Search..." 
                                   class="w-48 lg:w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                        </div>
                        
                        <!-- Mobile Search Button -->
                        <button class="sm:hidden p-2 text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </button>
                        
                        <!-- Notifications -->
                        <button class="relative p-2 text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.828 7l2.586 2.586a2 2 0 002.828 0L12.828 7H4.828z"></path>
                            </svg>
                            <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-secondary ring-2 ring-white"></span>
                        </button>
                        
                        <!-- User Menu -->
                        <div class="relative">
                            <button wire:click="$toggle('showUserDropdown')" class="flex items-center space-x-1 sm:space-x-2 text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center">
                                     <span class="text-white font-medium text-xs sm:text-sm">{{ $userName[0] }}</span>
                                </div>
                                <span class="text-gray-700 hidden sm:block">{{ $userName }}</span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            
                            @if($showUserDropdown)
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200" 
                                 wire:click.away="$set('showUserDropdown', false)">
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        Profile
                                    </div>
                                </a>
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        Settings
                                    </div>
                                </a>
                                <div class="border-t border-gray-100"></div>
                                <button wire:click="logout" class="w-full text-left block px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                        </svg>
                                        Logout
                                    </div>
                                </button>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Content Area -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 sm:p-6">
            @if($activeTab === 'overview')
                <div class="space-y-4 sm:space-y-6">
                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 border border-gray-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-primary rounded-md flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                                    <p class="text-xs sm:text-sm font-medium text-gray-500 truncate">Total Portfolio Value</p>
                                    <p class="text-lg sm:text-2xl font-semibold text-gray-900">$125,430</p>
                                    <p class="text-xs sm:text-sm text-green-600">+12.5% from last month</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 border border-gray-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-secondary rounded-md flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                                    <p class="text-xs sm:text-sm font-medium text-gray-500 truncate">Today's Gain</p>
                                    <p class="text-lg sm:text-2xl font-semibold text-gray-900">+$2,340</p>
                                    <p class="text-xs sm:text-sm text-green-600">+1.89%</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 border border-gray-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                                    <p class="text-xs sm:text-sm font-medium text-gray-500 truncate">Active Products</p>
                                    <p class="text-lg sm:text-2xl font-semibold text-gray-900">24</p>
                                    <p class="text-xs sm:text-sm text-gray-600">Across 3 categories</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6 border border-gray-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-3 sm:ml-4 min-w-0 flex-1">
                                    <p class="text-xs sm:text-sm font-medium text-gray-500 truncate">Pending Orders</p>
                                    <p class="text-lg sm:text-2xl font-semibold text-gray-900">3</p>
                                    <p class="text-xs sm:text-sm text-gray-600">2 buy, 1 sell</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                            <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
                            </div>
                            <livewire:dashboard.activity.mainactivity />
                        </div>
                    </div>
                    <!-- Recent Activity -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
                            <h3 class="text-base sm:text-lg font-medium text-gray-900">Recent Activity</h3>
                        </div>
                        <div class="p-4 sm:p-6">
                            <div class="space-y-3 sm:space-y-4">
                                <div class="flex items-start space-x-3 sm:space-x-4">
                                    <div class="w-2 h-2 bg-green-500 rounded-full mt-2 flex-shrink-0"></div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs sm:text-sm text-gray-900">Added 100 units of Product A at $150.25</p>
                                        <p class="text-xs text-gray-500">2 hours ago</p>
                                    </div>
                                    <span class="text-xs sm:text-sm font-medium text-green-600 flex-shrink-0">+$15,025</span>
                                </div>
                                <div class="flex items-start space-x-3 sm:space-x-4">
                                    <div class="w-2 h-2 bg-red-500 rounded-full mt-2 flex-shrink-0"></div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs sm:text-sm text-gray-900">Removed 50 units of Product B at $245.80</p>
                                        <p class="text-xs text-gray-500">4 hours ago</p>
                                    </div>
                                    <span class="text-xs sm:text-sm font-medium text-red-600 flex-shrink-0">+$12,290</span>
                                </div>
                                <div class="flex items-start space-x-3 sm:space-x-4">
                                    <div class="w-2 h-2 bg-blue-500 rounded-full mt-2 flex-shrink-0"></div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs sm:text-sm text-gray-900">Revenue from Product C: $125.50</p>
                                        <p class="text-xs text-gray-500">1 day ago</p>
                                    </div>
                                    <span class="text-xs sm:text-sm font-medium text-blue-600 flex-shrink-0">+$125.50</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @elseif($activeTab === 'stocks')
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
                            <h3 class="text-base sm:text-lg font-medium text-gray-900">Product Portfolio</h3>
                            <button class="bg-primary text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-primary-dark transition-colors w-full sm:w-auto">
                                Add Product
                            </button>
                        </div>
                    </div>
                    
                    <!-- Desktop Table -->
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Change</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">PRD001</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Product A</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">100</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$150.25</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$15,025</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">+2.5%</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button class="text-primary hover:text-primary-dark mr-3">Edit</button>
                                        <button class="text-red-600 hover:text-red-800">Remove</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">PRD002</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Product B</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">50</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$245.80</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$12,290</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">-1.2%</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button class="text-primary hover:text-primary-dark mr-3">Edit</button>
                                        <button class="text-red-600 hover:text-red-800">Remove</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">PRD003</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Product C</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">75</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$330.15</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$24,761</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">+0.8%</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button class="text-primary hover:text-primary-dark mr-3">Edit</button>
                                        <button class="text-red-600 hover:text-red-800">Remove</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Mobile Cards -->
                    <div class="sm:hidden divide-y divide-gray-200">
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-900">PRD001</span>
                                    <span class="text-xs text-green-600 bg-green-100 px-2 py-1 rounded-full">+2.5%</span>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium text-gray-900">$15,025</p>
                                    <p class="text-xs text-gray-500">100 units</p>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mb-3">Product A • $150.25</p>
                            <div class="flex space-x-2">
                                <button class="flex-1 bg-primary text-white px-3 py-2 rounded-md text-xs font-medium">Edit</button>
                                <button class="flex-1 bg-red-600 text-white px-3 py-2 rounded-md text-xs font-medium">Remove</button>
                            </div>
                        </div>
                        
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-900">PRD002</span>
                                    <span class="text-xs text-red-600 bg-red-100 px-2 py-1 rounded-full">-1.2%</span>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium text-gray-900">$12,290</p>
                                    <p class="text-xs text-gray-500">50 units</p>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mb-3">Product B • $245.80</p>
                            <div class="flex space-x-2">
                                <button class="flex-1 bg-primary text-white px-3 py-2 rounded-md text-xs font-medium">Edit</button>
                                <button class="flex-1 bg-red-600 text-white px-3 py-2 rounded-md text-xs font-medium">Remove</button>
                            </div>
                        </div>
                        
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-900">PRD003</span>
                                    <span class="text-xs text-green-600 bg-green-100 px-2 py-1 rounded-full">+0.8%</span>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium text-gray-900">$24,761</p>
                                    <p class="text-xs text-gray-500">75 units</p>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mb-3">Product C • $330.15</p>
                            <div class="flex space-x-2">
                                <button class="flex-1 bg-primary text-white px-3 py-2 rounded-md text-xs font-medium">Edit</button>
                                <button class="flex-1 bg-red-600 text-white px-3 py-2 rounded-md text-xs font-medium">Remove</button>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ ucfirst($activeTab) }} Content</h3>
                    <p class="text-gray-600">This section is under development. Content for {{ $activeTab }} will be displayed here.</p>
                </div>
            @endif
        </main>
    </div>
</div>
