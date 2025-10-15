<x-head :title="$title ?? 'Dashboard - TelconGH'" />

<body class="bg-gray-50 min-h-screen">
    <div class="flex h-screen bg-gray-50">
        <!-- Desktop Sidebar -->
        @livewire('sidebar')
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="px-4 sm:px-6 py-4">
                    <div class="flex items-center justify-between">
                        <!-- Mobile menu button -->
                        <div class="flex items-center">
                            <button onclick="toggleMobileSidebar()" class="lg:hidden -ml-0.5 -mt-0.5 h-12 w-12 inline-flex items-center justify-center rounded-md text-gray-500 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>
                            <div class="ml-4 lg:ml-0">
                                <div class="flex items-center">
                                    @php
                                        $selectedBusiness = session('selected_business');
                                        $businessName = is_array($selectedBusiness) ? ($selectedBusiness['name'] ?? 'No Business') : ($selectedBusiness ?? 'No Business');
                                    @endphp
                                    <span class="text-xs sm:text-sm text-gray-600 hidden sm:block">Business: <span class="text-blue-400 font-medium">{{ $businessName }}</span></span>
                                </div>
                                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Dashboard</h1>
                                <p class="text-xs sm:text-sm text-gray-600 hidden sm:block">Welcome back! Here's what's happening with your business.</p>
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
                                @php
                                    $userName = session('user_name', 'User');
                                    $userAvatar = session('user_avatar');
                                    $userInitials = strtoupper(substr($userName, 0, 1));
                                @endphp
                                <button onclick="toggleUserDropdown()" class="flex items-center space-x-1 sm:space-x-2 text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                    <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center">
                                        @if($userAvatar)
                                            <img src="{{ $userAvatar }}" alt="{{ $userName }}" class="w-8 h-8 rounded-full object-cover">
                                        @else
                                            <span class="text-white font-medium text-xs sm:text-sm">{{ $userInitials }}</span>
                                        @endif
                                    </div>
                                    <span class="text-gray-700 hidden sm:block">{{ $userName }}</span>
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                
                                <div id="userDropdown" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200">
                                    <!-- User Info Header -->
                                    <div class="px-4 py-3 border-b border-gray-100">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center">
                                                @if($userAvatar)
                                                    <img src="{{ $userAvatar }}" alt="{{ $userName }}" class="w-10 h-10 rounded-full object-cover">
                                                @else
                                                    <span class="text-white font-medium text-sm">{{ $userInitials }}</span>
                                                @endif
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-gray-900">{{ $userName }}</p>
                                                <p class="text-xs text-gray-500">{{ session('user_email', 'No email') }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    
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
                                    <a href="/logout" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                            </svg>
                                            Logout
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Content Area -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 sm:p-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div id="mobileSidebar" class="hidden fixed inset-0 z-40 lg:hidden">
        <div class="fixed inset-0 bg-gray-600 bg-opacity-75" onclick="toggleMobileSidebar()"></div>
        <div class="relative flex-1 flex flex-col max-w-xs w-full bg-white">
            <div class="absolute top-0 right-0 -mr-12 pt-2">
                <button onclick="toggleMobileSidebar()" class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="flex-1 h-0 pt-5 pb-4 overflow-y-auto">
                <div class="flex-shrink-0 flex items-center px-4">
                    <img src="{{ asset('logo/telcongh_main.png') }}" alt="TelconGH" class="h-12 w-auto">
                </div>
                
                <!-- Mobile Business Switcher -->
                <div class="mt-4 px-4">
                    <div class="bg-gray-50 rounded-lg p-3">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Current Business</h3>
                        <div class="relative">
                            @php
                                $selectedBusiness = session('selected_business');
                                $businessName = is_array($selectedBusiness) ? ($selectedBusiness['name'] ?? 'No Business') : ($selectedBusiness ?? 'No Business');
                                $businesses = session('user_businesses', []);
                            @endphp
                            <button onclick="toggleMobileBusinessDropdown()" 
                                    class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                <div class="flex flex-col text-left">
                                    <span class="font-medium text-blue-400">{{ $businessName }}</span>
                                    <span class="text-xs text-blue-400">{{ $businessName }}</span>
                                    @php
                                        $currentBusiness = !empty($businesses) ? collect($businesses)->firstWhere('name', $businessName) : null;
                                    @endphp
                                    @if($currentBusiness)
                                        <span class="text-xs text-primary">{{ $currentBusiness['business_code'] ?? 'N/A' }}</span>
                                    @endif
                                </div>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            
                            <div id="mobileBusinessDropdown" class="hidden absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg">
                                <div class="py-1">
                                    @if(!empty($businesses) && is_array($businesses))
                                        @foreach($businesses as $business)
                                        <button onclick="switchMobileBusiness('{{ $business['name'] ?? 'Unknown' }}')" 
                                                class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-primary hover:text-white transition-colors">
                                            <div class="flex flex-col">
                                                <span class="font-medium">{{ $business['name'] ?? 'Unknown Business' }}</span>
                                                <span class="text-xs text-primary">{{ $business['business_code'] ?? 'N/A' }}</span>
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
                        </div>
                    </div>
                </div>
                
                <nav class="mt-5 px-2 space-y-1">
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
                    <a href="{{ route('dashboard.reports') }}" 
                       class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('dashboard.reports') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Reports
                    </a>
                    <a href="{{ route('dashboard.analytics') }}" 
                       class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('dashboard.analytics') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Analytics
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
                    <a href="{{ route('logout') }}" 
                       class="w-full flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors text-red-600 hover:bg-red-50">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        Logout
                    </a>
                </nav>
            </div>
        </div>
</div>

    <script>
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('mobileSidebar');
            sidebar.classList.toggle('hidden');
        }

        function toggleUserDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('hidden');
        }

        function toggleMobileBusinessDropdown() {
            const dropdown = document.getElementById('mobileBusinessDropdown');
            dropdown.classList.toggle('hidden');
        }

        function switchMobileBusiness(businessName) {
            // Update the selected business in session via AJAX
            fetch('/dashboard/switch-business', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    business_name: businessName
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close the dropdown
                    document.getElementById('mobileBusinessDropdown').classList.add('hidden');
                    // Reload the page to reflect the changes
                    window.location.reload();
                } else {
                    console.error('Failed to switch business:', data.message);
                }
            })
            .catch(error => {
                console.error('Error switching business:', error);
            });
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            const userDropdown = document.getElementById('userDropdown');
            const mobileBusinessDropdown = document.getElementById('mobileBusinessDropdown');
            const button = event.target.closest('button');
            
            // Close user dropdown
            if (!button || !button.onclick || button.onclick.toString().indexOf('toggleUserDropdown') === -1) {
                userDropdown.classList.add('hidden');
            }
            
            // Close mobile business dropdown
            if (!button || !button.onclick || button.onclick.toString().indexOf('toggleMobileBusinessDropdown') === -1) {
                mobileBusinessDropdown.classList.add('hidden');
            }
        });

        // Listen for business switched event from Livewire
        document.addEventListener('livewire:init', () => {
            Livewire.on('business-switched', () => {
                window.location.reload();
            });
        });
    </script>
    
    @livewireScripts
</body>
</html>
