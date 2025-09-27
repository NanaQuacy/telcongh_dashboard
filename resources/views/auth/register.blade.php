<x-layouts.app title="Register - TelconGH">
    <x-header />
    
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
              
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Create your account
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Or
                    <a href="/login" class="font-medium text-primary hover:text-primary-dark">
                        sign in to your existing account
                    </a>
                </p>
            </div>
            
            <livewire:auth.register />
        </div>
    </div>
</x-layouts.app>
