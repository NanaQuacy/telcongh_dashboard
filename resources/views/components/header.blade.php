<header class="bg-white shadow-sm border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo -->
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <img src="{{ asset('logo/telcongh_main.png') }}" alt="TelconGH" class="h-24 w-auto">
                </div>
            </div>

            <!-- Navigation -->
            <nav class="hidden md:flex space-x-8">
                <a href="/" class="text-gray-700 hover:text-primary px-3 py-2 rounded-md text-sm font-medium transition-colors">
                    Home
                </a>
                <a href="/login" class="text-gray-700 hover:text-primary px-3 py-2 rounded-md text-sm font-medium transition-colors">
                    Login
                </a>
                <a href="/register" class="bg-primary text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-primary-dark transition-colors">
                    Register
                </a>
            </nav>

            <!-- Mobile menu button -->
            <div class="md:hidden">
                <button type="button" class="text-gray-700 hover:text-primary focus:outline-none focus:text-primary">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</header>