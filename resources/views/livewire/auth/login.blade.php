<?php

use Livewire\Volt\Component;
use App\Services\Auth\AuthenticationService;
use Illuminate\Support\Facades\Log;

new class extends Component
{
    public $email = '';
    public $password = '';
    public $remember = false;
    public $isLoading = false;
    public $errorMessage = '';

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required|min:6',
    ];

    protected $messages = [
        'email.required' => 'Email address is required.',
        'email.email' => 'Please enter a valid email address.',
        'password.required' => 'Password is required.',
        'password.min' => 'Password must be at least 6 characters.',
    ];

    public function login(AuthenticationService $authService)
    {
        $this->isLoading = true;
        $this->errorMessage = '';

        // Validate the form
        $this->validate();

        try {
            // Attempt to login using the authentication service
            $response = $authService->login($this->email, $this->password, $this->remember);
            //dd($response);
            if ($response->isSuccessful()) {
            
                // Login successful - AuthenticationService already stores session data
                session()->flash('success', 'Welcome back! You have been logged in successfully.');
                return redirect('/dashboard');
            } else {
                // Login failed
                $this->errorMessage = $response->message ?? 'Login failed. Please check your credentials.';
                
                // Log the failed attempt
                Log::warning('Login attempt failed', [
                    'email' => $this->email,
                    'errors' => $response->getErrors()
                ]);
            }
        } catch (\Exception $e) {
            // Handle any exceptions
            $this->errorMessage = 'An error occurred during login. Please try again.';
            
            Log::error('Login exception', [
                'email' => $this->email,
                'error' => $e->getMessage()
            ]);
        } finally {
            $this->isLoading = false;
        }
    }

    public function updated($propertyName)
    {
        // Clear error message when user starts typing
        if ($this->errorMessage && in_array($propertyName, ['email', 'password'])) {
            $this->errorMessage = '';
        }
        
        // Validate individual fields as user types
        $this->validateOnly($propertyName);
    }

    public function clearErrors()
    {
        $this->errorMessage = '';
        $this->resetErrorBag();
    }
};
?>
<div>
@if($errorMessage)
<div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div class="ml-3">
            <p class="text-sm text-red-800">{{ $errorMessage }}</p>
        </div>
        <div class="ml-auto pl-3">
            <button wire:click="clearErrors" class="text-red-400 hover:text-red-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
</div>
@endif

<!-- Success Message -->
@if(session('success'))
<div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-md">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div class="ml-3">
            <p class="text-sm text-green-800">{{ session('success') }}</p>
        </div>
    </div>
</div>
@endif

<form class="mt-8 space-y-6" wire:submit="login">
    @csrf
    <div class="rounded-md shadow-sm -space-y-px">
        <div>
            <label for="email" class="sr-only">Email address</label>
            <input wire:model.blur="email" 
                   id="email" 
                   name="email" 
                   type="email" 
                   autocomplete="email" 
                   required 
                   class="appearance-none rounded-none relative block w-full px-3 py-2 border {{ $errors->has('email') ? 'border-red-300' : 'border-gray-300' }} placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm" 
                   placeholder="Email address"
                   @if($isLoading) disabled @endif>
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="password" class="sr-only">Password</label>
            <input wire:model.blur="password" 
                   id="password" 
                   name="password" 
                   type="password" 
                   autocomplete="current-password" 
                   required 
                   class="appearance-none rounded-none relative block w-full mt-2 px-3 py-2 border {{ $errors->has('password') ? 'border-red-300' : 'border-gray-300' }} placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm" 
                   placeholder="Password"
                   @if($isLoading) disabled @endif>
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="flex items-center justify-between">
        <div class="flex items-center">
            <input wire:model="remember" 
                   id="remember" 
                   name="remember" 
                   type="checkbox" 
                   class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                   @if($isLoading) disabled @endif>
            <label for="remember" class="ml-2 block text-sm text-gray-900">
                Remember me
            </label>
        </div>

        <div class="text-sm">
            <a href="#" class="font-medium text-primary hover:text-primary-dark">
                Forgot your password?
            </a>
        </div>
    </div>

    <div>
        <button type="submit" 
                class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                @if($isLoading) disabled @endif>
            @if($isLoading)
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Signing in...
            @else
                Sign in
            @endif
        </button>
    </div>
</form>
</div>
