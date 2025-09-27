<?php

use Livewire\Volt\Component;
use App\Services\Auth\AuthenticationService;
use App\Http\Integrations\TelconApiConnector;

new class extends Component
{
    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $business_code = '';
    public $phone = '';
    public $terms = false;
    public $errorMessage = '';
    public $apiErrors = [];

    public function register()
    {
        $this->errorMessage = '';
        $this->apiErrors = [];

        $authService = new AuthenticationService(new TelconApiConnector());
        $response = $authService->register($this->name, $this->email,$this->phone, $this->password, $this->password_confirmation, $this->business_code);

        if ($response->isSuccessful()) {
            return redirect('/login');
        } else {
            // Set a general error message if available
            $this->errorMessage = $response->message ?? 'Registration failed. Please try again.';

            // Set API validation errors if available
            if (!empty($response->errors) && is_array($response->errors)) {
                $this->apiErrors = $response->errors;
            }
        }
    }
};?>

<div>
    @if($errorMessage)
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10A8 8 0 11 2 10a8 8 0 0116 0zm-7-4a1 1 0 10-2 0v4a1 1 0 002 0V6zm-1 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700">
                        {{ $errorMessage }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if(!empty($apiErrors))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
            <ul class="list-disc pl-5 text-sm text-red-700">
                @foreach($apiErrors as $field => $messages)
                    @foreach((array)$messages as $msg)
                        <li>{{ is_numeric($field) ? $msg : ucfirst($field) . ': ' . $msg }}</li>
                    @endforeach
                @endforeach
            </ul>
        </div>
    @endif

    <form class="mt-8 space-y-6" wire:submit="register">
        @csrf
        <div class="space-y-4">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                <input wire:model="name" id="name" name="name" type="text" autocomplete="name" required 
                       class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" 
                       placeholder="Enter your full name">
            </div>
            
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                <input wire:model="email" id="email" name="email" type="email" autocomplete="email" required 
                       class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" 
                       placeholder="Enter your email">
            </div>
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                <input wire:model="phone" id="phone" name="phone" type="text" autocomplete="phone" required 
                       class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" 
                       placeholder="Phone Number eg: 0594862524">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input wire:model="password" id="password" name="password" type="password" autocomplete="new-password" required 
                       class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" 
                       placeholder="Create a password">
            </div>
            
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                <input wire:model="password_confirmation" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required 
                       class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" 
                       placeholder="Confirm your password">
            </div>
            <div>
                <label for="business_code" class="block text-sm font-medium text-gray-700">Organization Code</label>
                <input wire:model="business_code" id="business_code" name="business_code" type="text"  required 
                       class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" 
                       placeholder="Confirm your password">
            </div>
        </div>

        <div class="flex items-center">
            <input wire:model="terms" id="terms" name="terms" type="checkbox" required
                   class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
            <label for="terms" class="ml-2 block text-sm text-gray-900">
                I agree to the
                <a href="#" class="text-primary hover:text-primary-dark">Terms and Conditions</a>
                and
                <a href="#" class="text-primary hover:text-primary-dark">Privacy Policy</a>
            </label>
        </div>

        <div>
            <button type="submit" 
                    class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                Create Account
            </button>
        </div>
    </form>
</div>