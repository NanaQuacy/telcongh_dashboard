<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Services\NetworkService;
use App\Http\Integrations\TelconApiConnector;

new class extends Component {
    use WithFileUploads;
    
    public $serviceData = null;
    public $serviceLoaded = false;
    public $serviceError = '';
    public $selectedServiceId = null;
    
    // Step management
    public $currentStep = 1;
    
    // Step 1: SIM Verification
    public $simSerial = '';
    public $phoneNumber = '';
    
    // Step 2: Customer Details
    public $firstName = '';
    public $lastName = '';
    public $email = '';
    public $address = '';
    
    // Step 3: ID and Photo
    public $idPhoto;
    public $passPhoto;
    
    // Step 4: Confirmation
    public $confirmDetails = false;
    
    protected $rules = [
        'simSerial' => 'required|string|min:10',
        'phoneNumber' => 'required|string|min:10',
        'firstName' => 'required|string|min:2',
        'lastName' => 'required|string|min:2',
        'email' => 'nullable|email',
        'address' => 'nullable|string',
        'idPhoto' => 'required|image|max:2048',
        'passPhoto' => 'required|image|max:2048',
        'confirmDetails' => 'required|accepted',
    ];
    
    public function mount() {
        $this->selectedServiceId = session('selected_service');
        
        if ($this->selectedServiceId) {
            $this->loadServiceData();
        } else {
            $this->serviceError = 'No service selected. Please go back and select a service.';
        }
    }
    
    public function loadServiceData() {
        if (!$this->selectedServiceId) {
            $this->serviceError = 'No service ID available';
            return;
        }
        
        $networkService = new NetworkService(new TelconApiConnector());
        $response = $networkService->getNetworkServicePricing((int)$this->selectedServiceId);
        
        if ($response->isSuccessful()) {
            $this->serviceData = $response->getData();
            $this->serviceLoaded = true;
            $this->serviceError = '';
        } else {
            $this->serviceData = null;
            $this->serviceLoaded = false;
            $this->serviceError = $response->getMessage();
        }
    }
    
    public function nextStep() {
        $this->validateStep();
        $this->currentStep++;
    }
    
    public function previousStep() {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }
    
    public function validateStep() {
        switch ($this->currentStep) {
            case 1:
                $this->validate([
                    'simSerial' => 'required|string|min:10',
                    'phoneNumber' => 'required|string|min:10',
                ]);
                break;
            case 2:
                $this->validate([
                    'firstName' => 'required|string|min:2',
                    'lastName' => 'required|string|min:2',
                    'email' => 'nullable|email',
                ]);
                break;
            case 3:
                $this->validate([
                    'idPhoto' => 'required|image|max:2048',
                    'passPhoto' => 'required|image|max:2048',
                ]);
                break;
            case 4:
                $this->validate([
                    'confirmDetails' => 'required|accepted',
                ]);
                break;
        }
    }
    
    public function executeService() {
        $this->validate([
            'confirmDetails' => 'required|accepted',
        ]);
        
        // Here you would implement the actual service execution logic
        // For now, we'll just show a success message and redirect
        
        session()->flash('success', 'Service executed successfully!');
        return redirect()->route('dashboard.networks');
    }
    
    public function goBack() {
        return redirect()->route('dashboard.networks');
    }
}; ?>

<div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Fixed Header -->
    <div class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-10">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="py-4">
                <div class="flex items-center">
                    <button wire:click="goBack" class="mr-4 p-2 text-gray-400 hover:text-gray-600 transition-colors rounded-lg hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900">Perform Service</h1>
                        <p class="text-sm text-gray-500">Execute the selected network service</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scrollable Content -->
    <div class="flex-1 overflow-y-auto">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @if($serviceError)
            <!-- Error State -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                <div class="flex items-center">
                    <svg class="w-8 h-8 text-red-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <h3 class="text-lg font-medium text-red-800">Error Loading Service</h3>
                        <p class="text-red-600">{{ $serviceError }}</p>
                    </div>
                </div>
                <div class="mt-4">
                    <button wire:click="goBack" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition-colors">
                        Go Back
                    </button>
                </div>
            </div>
        @elseif($serviceLoaded && $serviceData)
            <!-- Service Details -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <!-- Service Header -->
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-6 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-14 h-14 rounded-xl flex items-center justify-center mr-4 shadow-sm" style="background-color: {{ $serviceData['network_service']['network']['color_code'] ?? '#6B7280' }}">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">{{ $serviceData['network_service']['service']['name'] ?? 'Service' }}</h2>
                                <p class="text-sm text-gray-600 mt-1">{{ $serviceData['network_service']['network']['name'] ?? 'Network' }} Network</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-3xl font-bold text-green-600 mb-1">₵{{ number_format($serviceData['selling_price'], 2) }}</div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $serviceData['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $serviceData['is_active'] ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Form Content -->
                <div class="px-6 py-6">
                    @if(isset($serviceData['network_service']['service']['description']) && $serviceData['network_service']['service']['description'])
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Description</h3>
                        <p class="text-gray-600 text-sm">{{ $serviceData['network_service']['service']['description'] }}</p>
                    </div>
                    @endif

                    <!-- 4-Step Service Execution Form -->
                    <!-- Progress Steps -->
                    <div class="mb-8 p-6 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-100">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="flex items-center justify-center w-10 h-10 rounded-full {{ $currentStep >= 1 ? 'bg-blue-600 text-white shadow-lg' : 'bg-white text-gray-400 border-2 border-gray-200' }}">
                                    <span class="text-sm font-semibold">1</span>
                                </div>
                                <span class="ml-3 text-sm font-semibold {{ $currentStep >= 1 ? 'text-blue-700' : 'text-gray-500' }}">SIM Verification</span>
                            </div>
                            <div class="flex-1 h-1 mx-4 rounded-full {{ $currentStep > 1 ? 'bg-blue-600' : 'bg-gray-200' }}"></div>
                            <div class="flex items-center">
                                <div class="flex items-center justify-center w-10 h-10 rounded-full {{ $currentStep >= 2 ? 'bg-blue-600 text-white shadow-lg' : 'bg-white text-gray-400 border-2 border-gray-200' }}">
                                    <span class="text-sm font-semibold">2</span>
                                </div>
                                <span class="ml-3 text-sm font-semibold {{ $currentStep >= 2 ? 'text-blue-700' : 'text-gray-500' }}">Customer Details</span>
                            </div>
                            <div class="flex-1 h-1 mx-4 rounded-full {{ $currentStep > 2 ? 'bg-blue-600' : 'bg-gray-200' }}"></div>
                            <div class="flex items-center">
                                <div class="flex items-center justify-center w-10 h-10 rounded-full {{ $currentStep >= 3 ? 'bg-blue-600 text-white shadow-lg' : 'bg-white text-gray-400 border-2 border-gray-200' }}">
                                    <span class="text-sm font-semibold">3</span>
                                </div>
                                <span class="ml-3 text-sm font-semibold {{ $currentStep >= 3 ? 'text-blue-700' : 'text-gray-500' }}">ID & Photo</span>
                            </div>
                            <div class="flex-1 h-1 mx-4 rounded-full {{ $currentStep > 3 ? 'bg-blue-600' : 'bg-gray-200' }}"></div>
                            <div class="flex items-center">
                                <div class="flex items-center justify-center w-10 h-10 rounded-full {{ $currentStep >= 4 ? 'bg-blue-600 text-white shadow-lg' : 'bg-white text-gray-400 border-2 border-gray-200' }}">
                                    <span class="text-sm font-semibold">4</span>
                                </div>
                                <span class="ml-3 text-sm font-semibold {{ $currentStep >= 4 ? 'text-blue-700' : 'text-gray-500' }}">Confirmation</span>
                            </div>
                        </div>
                    </div>

                    <!-- Step Content -->
                    <div>
                        <!-- Step 1: SIM Card Verification -->
                        @if($currentStep == 1)
                        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                            <h3 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
                                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                SIM Card Verification
                            </h3>
                            <div class="space-y-6">
                                <div>
                                    <label for="sim_serial" class="block text-sm font-semibold text-gray-700 mb-2">SIM Card Serial Number</label>
                                    <input type="text" id="sim_serial" wire:model="simSerial" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                           placeholder="Enter SIM card serial number" required>
                                    @error('simSerial') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="phone_number" class="block text-sm font-semibold text-gray-700 mb-2">Phone Number</label>
                                    <input type="tel" id="phone_number" wire:model="phoneNumber" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                           placeholder="Enter phone number" required>
                                    @error('phoneNumber') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Step 2: Customer Details -->
                        @if($currentStep == 2)
                        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                            <h3 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
                                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                                Customer Details
                            </h3>
                            <div class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="first_name" class="block text-sm font-semibold text-gray-700 mb-2">First Name</label>
                                        <input type="text" id="first_name" wire:model="firstName" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                               placeholder="Enter first name" required>
                                        @error('firstName') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="last_name" class="block text-sm font-semibold text-gray-700 mb-2">Last Name</label>
                                        <input type="text" id="last_name" wire:model="lastName" 
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                               placeholder="Enter last name" required>
                                        @error('lastName') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div>
                                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
                                    <input type="email" id="email" wire:model="email" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                           placeholder="Enter email address">
                                    @error('email') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="address" class="block text-sm font-semibold text-gray-700 mb-2">Address</label>
                                    <textarea id="address" wire:model="address" rows="3"
                                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors resize-none"
                                              placeholder="Enter customer address"></textarea>
                                    @error('address') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Step 3: ID and Photo Upload -->
                        @if($currentStep == 3)
                        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                            <h3 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
                                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                ID & Photo Verification
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">ID Card Photo</label>
                                    <div class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-blue-400 transition-colors">
                                        <input type="file" wire:model="idPhoto" accept="image/*" class="hidden" id="id-photo">
                                        <label for="id-photo" class="cursor-pointer">
                                            @if($idPhoto)
                                                <img src="{{ $idPhoto->temporaryUrl() }}" alt="ID Photo" class="mx-auto h-40 w-auto rounded-lg shadow-sm">
                                                <p class="mt-3 text-sm font-medium text-green-600">✓ ID photo uploaded</p>
                                            @else
                                                <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                                <p class="mt-3 text-sm font-medium text-gray-600">Click to upload ID photo</p>
                                                <p class="text-xs text-gray-500 mt-1">Max 2MB, JPG/PNG</p>
                                            @endif
                                        </label>
                                    </div>
                                    @error('idPhoto') <span class="text-red-500 text-sm mt-2 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">Passport Photo</label>
                                    <div class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-blue-400 transition-colors">
                                        <input type="file" wire:model="passPhoto" accept="image/*" class="hidden" id="pass-photo">
                                        <label for="pass-photo" class="cursor-pointer">
                                            @if($passPhoto)
                                                <img src="{{ $passPhoto->temporaryUrl() }}" alt="Passport Photo" class="mx-auto h-40 w-auto rounded-lg shadow-sm">
                                                <p class="mt-3 text-sm font-medium text-green-600">✓ Passport photo uploaded</p>
                                            @else
                                                <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                </svg>
                                                <p class="mt-3 text-sm font-medium text-gray-600">Click to upload passport photo</p>
                                                <p class="text-xs text-gray-500 mt-1">Max 2MB, JPG/PNG</p>
                                            @endif
                                        </label>
                                    </div>
                                    @error('passPhoto') <span class="text-red-500 text-sm mt-2 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Step 4: Final Confirmation -->
                        @if($currentStep == 4)
                        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                            <h3 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
                                <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                Final Confirmation
                            </h3>
                            <div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-xl p-6 border border-green-100">
                                <h4 class="text-lg font-semibold text-gray-900 mb-4">Transaction Summary</h4>
                                <div class="space-y-4">
                                    <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                        <span class="text-sm font-medium text-gray-700">Service:</span>
                                        <span class="text-sm font-semibold text-gray-900">{{ $serviceData['network_service']['service']['name'] ?? 'Service' }}</span>
                                    </div>
                                    <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                        <span class="text-sm font-medium text-gray-700">Network:</span>
                                        <span class="text-sm font-semibold text-gray-900">{{ $serviceData['network_service']['network']['name'] ?? 'Network' }}</span>
                                    </div>
                                    <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                        <span class="text-sm font-medium text-gray-700">Phone Number:</span>
                                        <span class="text-sm font-semibold text-gray-900">{{ $phoneNumber }}</span>
                                    </div>
                                    <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                        <span class="text-sm font-medium text-gray-700">Customer:</span>
                                        <span class="text-sm font-semibold text-gray-900">{{ $firstName }} {{ $lastName }}</span>
                                    </div>
                                    <div class="flex justify-between items-center py-3 bg-white rounded-lg px-4">
                                        <span class="text-base font-bold text-gray-900">Total Amount:</span>
                                        <span class="text-2xl font-bold text-green-600">₵{{ number_format($serviceData['selling_price'], 2) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                                <label class="flex items-start">
                                    <input type="checkbox" wire:model="confirmDetails" class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">
                                        <span class="font-semibold">I confirm all details are correct</span> and authorize this transaction. By proceeding, I agree to the terms and conditions.
                                    </span>
                                </label>
                                @error('confirmDetails') <span class="text-red-500 text-sm mt-2 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        @endif

                        <!-- Navigation Buttons -->
                        <div class="flex justify-between items-center pt-8 border-t border-gray-200">
                            <div>
                                @if($currentStep > 1)
                                <button type="button" wire:click="previousStep" 
                                        class="flex items-center px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors font-medium">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                    Previous
                                </button>
                                @endif
                            </div>
                            <div class="flex space-x-4">
                                <button type="button" wire:click="goBack" 
                                        class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors font-medium">
                                    Cancel
                                </button>
                                @if($currentStep < 4)
                                <button type="button" wire:click="nextStep" 
                                        class="flex items-center px-8 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-semibold shadow-sm">
                                    Next
                                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                                @else
                                <button type="button" wire:click="executeService" 
                                        class="flex items-center px-8 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-semibold shadow-sm">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Execute Service
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <!-- Loading State -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                <div class="flex items-center justify-center py-16">
                    <div class="text-center">
                        <div class="w-16 h-16 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mx-auto mb-4"></div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Loading Service Details</h3>
                        <p class="text-sm text-gray-500">Please wait while we fetch the service information...</p>
                    </div>
                </div>
            </div>
        @endif
        </div>
    </div>
</div>
