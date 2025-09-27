
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
            @livewire('dashboard.activity.mainactivity')
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
