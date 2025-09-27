<x-layouts.app title="Login - TelconGH">
    <x-header />
    
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Sign in to your account
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Or
                    <a href="/register" class="font-medium text-primary hover:text-primary-dark">
                        create a new account
                    </a>
                </p>
            </div>
            
            <livewire:auth.login />
        </div>
    </div>
</x-layouts.app>
