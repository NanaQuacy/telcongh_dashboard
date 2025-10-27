<?php

use Livewire\Volt\Component;
use App\Services\BusinessOwnerRegistrationService;
use App\Http\Integrations\Data\RegisterBusinessOwnerResponse;

new class extends Component {
    // User Information
    public $name = '';
    public $phone = '';
    public $email = '';
    public $password = '';
    public $passwordConfirmation = '';
    
    // Business Information
    public $businessName = '';
    public $businessAddress = '';
    public $businessPhone = '';
    public $businessEmail = '';
    public $businessWebsite = '';
    public $businessDescription = '';
    
    // UI State
    public $loading = false;
    public $success = false;
    public $error = null;
    public $errors = [];
    public $currentStep = 1;
    public $totalSteps = 3;
    
    // Services - not persisted in Livewire
    protected $registrationService;

    public function mount()
    {
        // Service will be initialized when needed
    }

    protected function getRegistrationService()
    {
        if (!$this->registrationService) {
            $this->initializeService();
        }
        return $this->registrationService;
    }

    protected function initializeService()
    {
        try {
            \Log::info('Initializing registration service...');
            $this->registrationService = new \App\Services\BusinessOwnerRegistrationService();
            \Log::info('Registration service initialized successfully');
        } catch (\Exception $e) {
            \Log::error('Failed to initialize registration service: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            $this->error = 'Failed to initialize registration service. Please try again.';
        }
    }


    public function nextStep()
    {
        if ($this->currentStep < $this->totalSteps) {
            try {
                $this->validateStep();
                $this->currentStep++;
            } catch (\Illuminate\Validation\ValidationException $e) {
                // Validation errors are already handled in validateStep()
                // Don't proceed to next step
            }
        }
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function validateStep()
    {
        $this->errors = [];
        
        switch ($this->currentStep) {
            case 1:
                $this->validateStep1();
                break;
            case 2:
                $this->validateStep2();
                break;
            case 3:
                $this->validateStep3();
                break;
        }
    }

    protected function validateStep1()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
        ];

        $validator = \Illuminate\Support\Facades\Validator::make([
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
        ], $rules);

        if ($validator->fails()) {
            $this->errors = $validator->errors()->toArray();
            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }

    protected function validateStep2()
    {
        $rules = [
            'password' => 'required|string|min:8',
            'passwordConfirmation' => 'required|string|min:8|same:password',
        ];

        $validator = \Illuminate\Support\Facades\Validator::make([
            'password' => $this->password,
            'passwordConfirmation' => $this->passwordConfirmation,
        ], $rules);

        if ($validator->fails()) {
            $this->errors = $validator->errors()->toArray();
            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }

    protected function validateStep3()
    {
        $rules = [
            'businessName' => 'required|string|max:255',
            'businessAddress' => 'required|string|max:500',
            'businessPhone' => 'required|string|max:20',
            'businessEmail' => 'required|email|max:255',
            'businessWebsite' => 'nullable|url|max:255',
            'businessDescription' => 'nullable|string|max:1000',
        ];

        $validator = \Illuminate\Support\Facades\Validator::make([
            'businessName' => $this->businessName,
            'businessAddress' => $this->businessAddress,
            'businessPhone' => $this->businessPhone,
            'businessEmail' => $this->businessEmail,
            'businessWebsite' => $this->businessWebsite,
            'businessDescription' => $this->businessDescription,
        ], $rules);

        if ($validator->fails()) {
            $this->errors = $validator->errors()->toArray();
            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }

    public function register()
    {
        // Get service (initializes if needed)
        $service = $this->getRegistrationService();
        
        if (!$service) {
            \Log::error('Failed to initialize service');
            $this->error = 'Registration service is not available. Please refresh the page and try again.';
            return;
        }

        // Validate all steps before registration
        try {
            $this->validateStep1();
            $this->validateStep2();
            $this->validateStep3();
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation errors are already set in the individual validate methods
            return;
        }
        
        $this->loading = true;
        $this->error = null;
        $this->errors = [];

        try {
            $registrationData = [
                'name' => $this->name,
                'phone' => $this->phone,
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->passwordConfirmation,
                'business_name' => $this->businessName,
                'business_address' => $this->businessAddress,
                'business_phone' => $this->businessPhone,
                'business_email' => $this->businessEmail,
                'business_website' => $this->businessWebsite ?: null,
                'business_description' => $this->businessDescription ?: null,
            ];

            $result = $service->registerBusinessOwner($registrationData);

            if ($result && $result->isSuccessful()) {
                $this->success = true;
                
                // Store user data in session
                session([
                    'user_id' => $result->getUserId(),
                    'user_name' => $result->getUserName(),
                    'user_email' => $result->getUserEmail(),
                    'business_id' => $result->getBusinessId(),
                    'business_name' => $result->getBusinessName(),
                    'business_code' => $result->getBusinessCode(),
                    'auth_token' => $result->getToken(),
                ]);

                // Redirect to dashboard after successful registration
                $this->js('setTimeout(() => { window.location.href = "/dashboard"; }, 2000);');
            } else {
                $this->error = $result ? $result->getMessage() : 'Registration failed';
                $this->errors = $result && $result->hasErrors() ? $result->getErrors() : [];
            }
        } catch (\Exception $e) {
            $this->error = 'Registration failed: ' . $e->getMessage();
        } finally {
            $this->loading = false;
        }
    }

    public function resetForm()
    {
        $this->reset([
            'name', 'phone', 'email', 'password', 'passwordConfirmation',
            'businessName', 'businessAddress', 'businessPhone', 'businessEmail',
            'businessWebsite', 'businessDescription', 'loading', 'success', 'error', 'errors', 'currentStep'
        ]);
    }

    public function getStepTitle()
    {
        return match($this->currentStep) {
            1 => 'Personal Information',
            2 => 'Account Security',
            3 => 'Business Information',
            default => 'Registration'
        };
    }

    public function getStepDescription()
    {
        return match($this->currentStep) {
            1 => 'Tell us about yourself',
            2 => 'Create a secure password',
            3 => 'Tell us about your business',
            default => 'Complete your registration'
        };
    }
}; ?>

<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-6 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-2xl">
        <div class="text-center">
            <h2 class="text-3xl font-bold text-gray-900">Create Your Business Account</h2>
            <p class="mt-2 text-sm text-gray-600">
                Register your business and start managing your operations
            </p>
        </div>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-2xl">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
            <!-- Progress Bar -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">Step {{ $currentStep }} of {{ $totalSteps }}</span>
                    <span class="text-sm text-gray-500">{{ round(($currentStep / $totalSteps) * 100) }}% Complete</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                         style="width: {{ ($currentStep / $totalSteps) * 100 }}%"></div>
                </div>
            </div>

            <!-- Step Title -->
            <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-900">{{ $this->getStepTitle() }}</h3>
                <p class="text-sm text-gray-600">{{ $this->getStepDescription() }}</p>
            </div>

            @if($success)
                <!-- Success Message -->
                <div class="bg-green-50 border border-green-200 rounded-md p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-green-800">Registration Successful!</h3>
                            <div class="mt-2 text-sm text-green-700">
                                <p>Your business account has been created successfully. Redirecting to dashboard...</p>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <!-- Error Message -->
                @if($error)
                    <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Registration Failed</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <p>{{ $error }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <form wire:submit.prevent="register" class="space-y-6">
                    <!-- Step 1: Personal Information -->
                    @if($currentStep === 1)
                        <div class="space-y-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                                <input wire:model="name" type="text" id="name" required
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 {{ isset($errors['name']) ? 'border-red-300' : '' }}">
                                @if(isset($errors['name']))
                                    <p class="mt-1 text-sm text-red-600">{{ $errors['name'][0] }}</p>
                                @endif
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                <input wire:model="phone" type="tel" id="phone" required
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 {{ isset($errors['phone']) ? 'border-red-300' : '' }}">
                                @if(isset($errors['phone']))
                                    <p class="mt-1 text-sm text-red-600">{{ $errors['phone'][0] }}</p>
                                @endif
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                                <input wire:model="email" type="email" id="email" required
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 {{ isset($errors['email']) ? 'border-red-300' : '' }}">
                                @if(isset($errors['email']))
                                    <p class="mt-1 text-sm text-red-600">{{ $errors['email'][0] }}</p>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Step 2: Account Security -->
                    @if($currentStep === 2)
                        <div class="space-y-4">
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                                <input wire:model="password" type="password" id="password" required
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 {{ isset($errors['password']) ? 'border-red-300' : '' }}">
                                @if(isset($errors['password']))
                                    <p class="mt-1 text-sm text-red-600">{{ $errors['password'][0] }}</p>
                                @endif
                                <p class="mt-1 text-xs text-gray-500">Must be at least 8 characters long</p>
                            </div>

                            <div>
                                <label for="passwordConfirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                                <input wire:model="passwordConfirmation" type="password" id="passwordConfirmation" required
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 {{ isset($errors['passwordConfirmation']) ? 'border-red-300' : '' }}">
                                @if(isset($errors['passwordConfirmation']))
                                    <p class="mt-1 text-sm text-red-600">{{ $errors['passwordConfirmation'][0] }}</p>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Step 3: Business Information -->
                    @if($currentStep === 3)
                        <div class="space-y-4">
                            <div>
                                <label for="businessName" class="block text-sm font-medium text-gray-700">Business Name</label>
                                <input wire:model="businessName" type="text" id="businessName" required
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 {{ isset($errors['businessName']) ? 'border-red-300' : '' }}">
                                @if(isset($errors['businessName']))
                                    <p class="mt-1 text-sm text-red-600">{{ $errors['businessName'][0] }}</p>
                                @endif
                            </div>

                            <div>
                                <label for="businessAddress" class="block text-sm font-medium text-gray-700">Business Address</label>
                                <textarea wire:model="businessAddress" id="businessAddress" rows="3" required
                                          class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 {{ isset($errors['businessAddress']) ? 'border-red-300' : '' }}"></textarea>
                                @if(isset($errors['businessAddress']))
                                    <p class="mt-1 text-sm text-red-600">{{ $errors['businessAddress'][0] }}</p>
                                @endif
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="businessPhone" class="block text-sm font-medium text-gray-700">Business Phone</label>
                                    <input wire:model="businessPhone" type="tel" id="businessPhone" required
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 {{ isset($errors['businessPhone']) ? 'border-red-300' : '' }}">
                                    @if(isset($errors['businessPhone']))
                                        <p class="mt-1 text-sm text-red-600">{{ $errors['businessPhone'][0] }}</p>
                                    @endif
                                </div>

                                <div>
                                    <label for="businessEmail" class="block text-sm font-medium text-gray-700">Business Email</label>
                                    <input wire:model="businessEmail" type="email" id="businessEmail" required
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 {{ isset($errors['businessEmail']) ? 'border-red-300' : '' }}">
                                    @if(isset($errors['businessEmail']))
                                        <p class="mt-1 text-sm text-red-600">{{ $errors['businessEmail'][0] }}</p>
                                    @endif
                                </div>
                            </div>

                            <div>
                                <label for="businessWebsite" class="block text-sm font-medium text-gray-700">Business Website (Optional)</label>
                                <input wire:model="businessWebsite" type="url" id="businessWebsite"
                                       placeholder="https://www.example.com"
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 {{ isset($errors['businessWebsite']) ? 'border-red-300' : '' }}">
                                @if(isset($errors['businessWebsite']))
                                    <p class="mt-1 text-sm text-red-600">{{ $errors['businessWebsite'][0] }}</p>
                                @endif
                            </div>

                            <div>
                                <label for="businessDescription" class="block text-sm font-medium text-gray-700">Business Description (Optional)</label>
                                <textarea wire:model="businessDescription" id="businessDescription" rows="3"
                                          placeholder="Describe your business..."
                                          class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 {{ isset($errors['businessDescription']) ? 'border-red-300' : '' }}"></textarea>
                                @if(isset($errors['businessDescription']))
                                    <p class="mt-1 text-sm text-red-600">{{ $errors['businessDescription'][0] }}</p>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Navigation Buttons -->
                    <div class="flex justify-between pt-6">
                        <div>
                            @if($currentStep > 1)
                                <button type="button" wire:click="previousStep"
                                        class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                    Previous
                                </button>
                            @endif
                        </div>

<div>
                            @if($currentStep < $totalSteps)
                                <button type="button" wire:click="nextStep"
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Next
                                    <svg class="ml-2 -mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                            @else
                                <button type="submit" wire:loading.attr="disabled"
                                        class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50">
                                    <svg wire:loading wire:target="register" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span wire:loading.remove wire:target="register">Create Account</span>
                                    <span wire:loading wire:target="register">Creating Account...</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </form>
            @endif

            <!-- Login Link -->
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Already have an account?
                    <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-500">
                        Sign in here
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>
