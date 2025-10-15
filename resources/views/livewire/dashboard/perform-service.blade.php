<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Services\NetworkService;
use App\Http\Integrations\TelconApiConnector;
use App\Http\Integrations\Requests\CustomerServiceDetailsRequest;
use App\Http\Integrations\Requests\TransactionRequest;
use App\Http\Integrations\Requests\PaymentDetailsRequest;
use App\Http\Integrations\Requests\UpdateSimCardStatusRequest;

new class extends Component {
    use WithFileUploads;
    
    public $serviceData = null;
    public $serviceLoaded = false;
    public $serviceError = '';
    public $selectedServiceId = null;
    public $businessId = null;
    
    // Step management
    public $currentStep = 1;
    
    // Step 1: SIM Verification
    public $isNewSim = null; // null, '1' (new SIM), '0' (existing SIM)
    public $simSerial = '';
    public $phoneNumber = '';
    public $simVerificationStatus = null; // null, 'verifying', 'verified', 'failed'
    public $simVerificationMessage = '';
    
    // Step 2: Customer Details
    public $firstName = '';
    public $lastName = '';
    public $email = '';
    public $address = '';
    public $location = '';
    public $NOK_name = '';
    public $NOK_phone = '';
    public $Alternate_phone_number = '';
    public $MyMTNApp_Activation_Status = false;
    public $MomoApp_Activation_Status = false;
    public $ADS_Activation_Status = false;
    public $RGT_Activation_Status = false;
    public $Remarks = '';
    public $Reason_for_Action = '';
    public $Ticket_Number = '';
    
    // Step 3: ID and Photo
    public $frontId;
    public $backId;
    public $screenshots;
    public $document;
    
    // Step 4: Confirmation
    public $confirmDetails = false;
    
    // Loading state
    public $isExecuting = false;
    
    protected $rules = [
        'simSerial' => 'required|string|min:10|size:10',
        'phoneNumber' => 'required|string|min:10|size:10',
        'firstName' => 'required|string|min:2',
        'lastName' => 'required|string|min:2',
        'email' => 'nullable|email',
        'address' => 'nullable|string',
        'location' => 'nullable|string',
        'NOK_name' => 'nullable|string',
        'NOK_phone' => 'nullable|string',
        'Alternate_phone_number' => 'nullable|string',
        'MyMTNApp_Activation_Status' => 'nullable|boolean',
        'MomoApp_Activation_Status' => 'nullable|boolean',
        'ADS_Activation_Status' => 'nullable|boolean',
        'RGT_Activation_Status' => 'nullable|boolean',
        'Remarks' => 'nullable|string',
        'Reason_for_Action' => 'nullable|string',
        'Ticket_Number' => 'nullable|string',
        'frontId' => 'nullable|image|max:2048',
        'backId' => 'nullable|image|max:2048',
        'screenshots' => 'nullable|image|max:2048',
        'document' => 'nullable|image|max:2048',
        'confirmDetails' => 'required|accepted',
    ];
    
    public function mount() {
        $this->selectedServiceId = session('selected_service');
        $selectedBusiness = session('selected_business');
        $this->businessId = is_array($selectedBusiness) ? ($selectedBusiness['id'] ?? null) : null;
        
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
        if ($this->validateStep() === false) {
            return; // Stop if validation fails
        }
        
        // If using existing SIM, skip SIM verification and go directly to step 2
        if ($this->currentStep == 1 && $this->isNewSim == '0') {
            $this->currentStep = 2;
        } else {
        $this->currentStep++;
        }
    }
    
    public function previousStep() {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }
    
    public function validateStep() {
        switch ($this->currentStep) {
            case 1:
                $rules = [
                    'isNewSim' => 'required|in:0,1',
                    'phoneNumber' => 'required|string|min:10',
                ];
                
                // Only require SIM serial if using a new SIM
                if ($this->isNewSim == '1') {
                    $rules['simSerial'] = 'required|string|min:10';
                }
                
                $this->validate($rules);
                
                // Additional validation for new SIM: must be verified
                if ($this->isNewSim == '1' && $this->simVerificationStatus !== 'verified') {
                    $this->addError('simSerial', 'SIM card must be verified before proceeding.');
                    return false;
                }
                break;
            case 2:
                $this->validate([
                    'firstName' => 'required|string|min:2',
                    'lastName' => 'required|string|min:2',
                    'email' => 'nullable|email',
                    'location' => 'nullable|string',
                    'NOK_name' => 'nullable|string',
                    'NOK_phone' => 'nullable|string',
                    'Alternate_phone_number' => 'nullable|string',
                    'MyMTNApp_Activation_Status' => 'nullable|boolean',
                    'MomoApp_Activation_Status' => 'nullable|boolean',
                    'ADS_Activation_Status' => 'nullable|boolean',
                    'RGT_Activation_Status' => 'nullable|boolean',
                    'Remarks' => 'nullable|string',
                    'Reason_for_Action' => 'nullable|string',
                    'Ticket_Number' => 'nullable|string',
                ]);
                break;
            case 3:
                $this->validate([
                    'frontId' => 'nullable|image|max:2048',
                    'backId' => 'nullable|image|max:2048',
                    'screenshots' => 'nullable|image|max:2048',
                    'document' => 'nullable|image|max:2048',
                ]);
                break;
            case 4:
                $this->validate([
                    'confirmDetails' => 'required|accepted',
                ]);
                break;
        }
        
        return true; // Return true if all validations pass
    }
    
    public function checkActivationStatus() {
        // Check activation status for all services - all are optional
        $activationChecks = [
            'MyMTNApp' => $this->MyMTNApp_Activation_Status,
            'MomoApp' => $this->MomoApp_Activation_Status,
            'ADS' => $this->ADS_Activation_Status,
            'RGT' => $this->RGT_Activation_Status,
        ];
        
        $failedActivations = [];
        foreach ($activationChecks as $service => $status) {
            // Since all activation statuses are optional, we don't block execution
            // This is just for display purposes in the confirmation step
            if ($status === false) {
                $failedActivations[] = $service;
            }
        }
        
        return $failedActivations;
    }
    
    public function executeService() {
        $stockItemId = session('verified_sim_stock_item_id');
        
        $this->validate([
            'confirmDetails' => 'required|accepted',
        ]);
        
        // Note: Activation statuses are now optional, so we don't block execution
        // The checkActivationStatus() method is kept for display purposes only
        
        // Set loading state
        
        $this->isExecuting = true;
        
        try {
            $connector = new TelconApiConnector();
            
            // Get authentication token from session
            $token = session('auth_token');
            if (!$token) {
                throw new \Exception('Authentication token not found. Please log in again.');
            }
            
            // Step 1: Create Customer Service Details
            $customerServiceData = [
                'full_name' => trim($this->firstName . ' ' . $this->lastName),
                'phone_number' => $this->phoneNumber,
                'email' => $this->email,
                'location' => $this->location,
                'NOK_name' => $this->NOK_name,
                'NOK_phone' => $this->NOK_phone,
                'Alternate_phone_number' => $this->Alternate_phone_number,
                'SIM_serial_number' => $this->simSerial,
                'stock_item_id' => $stockItemId,
                'MyMTNApp_Activation_Status' => $this->MyMTNApp_Activation_Status,
                'MomoApp_Activation_Status' => $this->MomoApp_Activation_Status,
                'ADS_Activation_Status' => $this->ADS_Activation_Status,
                'RGT_Activation_Status' => $this->RGT_Activation_Status,
                'Remarks' => $this->Remarks,
                'Reason_for_Action' => $this->Reason_for_Action,
                'Ticket_Number' => $this->Ticket_Number,
                'Status' => 'completed',
                'Business_id' => $this->businessId,
                'is_active' => true,
            ];
                    // Log the prepared data for debugging
            \Log::info('Prepared customer service data', [
                'customer_service_data' => $customerServiceData,
                'required_fields_check' => [
                    'full_name' => !empty($customerServiceData['full_name']) ? 'OK' : 'EMPTY',
                    'phone_number' => !empty($customerServiceData['phone_number']) ? 'OK' : 'EMPTY',
                    'location' => !empty($customerServiceData['location']) ? 'OK' : 'EMPTY',
                    'NOK_name' => !empty($customerServiceData['NOK_name']) ? 'OK' : 'EMPTY',
                    'NOK_phone' => !empty($customerServiceData['NOK_phone']) ? 'OK' : 'EMPTY',
                    'Business_id' => !empty($customerServiceData['Business_id']) ? 'OK' : 'EMPTY',
                ]
            ]);
            
            // Add image files if they exist
            if ($this->frontId) {
                $customerServiceData['id_card_front'] = $this->frontId;
            }
            if ($this->backId) {
                $customerServiceData['id_card_back'] = $this->backId;
            }
            if ($this->screenshots) {
                $customerServiceData['screenshots'] = [$this->screenshots];
            }
            if ($this->document) {
                $customerServiceData['business_certificate'] = $this->document;
            }
            
            $customerServiceRequest = new CustomerServiceDetailsRequest($customerServiceData, $token);
            
            // Add debugging to see the request details
            $customerServiceRequest->debugRequest(function($pendingRequest, $psrRequest) {
                \Log::info('Saloon Request Debug', [
                    'method' => $psrRequest->getMethod(),
                    'uri' => (string)$psrRequest->getUri(),
                    'headers' => $psrRequest->getHeaders(),
                    'body_length' => strlen($psrRequest->getBody()->getContents()),
                ]);
            });
            
            \Log::info('About to send request', [
                'connector_base_url' => $connector->resolveBaseUrl(),
                'endpoint' => $customerServiceRequest->resolveEndpoint(),
                'full_url' => $connector->resolveBaseUrl() . $customerServiceRequest->resolveEndpoint(),
                'token_length' => strlen($token)
            ]);
            
            $customerServiceResponse = $connector->send($customerServiceRequest);
            
            // Check if the response is successful (status code 200-299)
            $statusCode = $customerServiceResponse->status();
            if ($statusCode < 200 || $statusCode >= 300) {
                // Log the full error response
                $errorBody = $customerServiceResponse->body();
                // Try to parse as JSON, but handle HTML responses gracefully
                $errorData = null;
                try {
                    $errorData = $customerServiceResponse->json();
                } catch (\Exception $e) {
                    \Log::warning('Could not parse error response as JSON', [
                        'error' => $e->getMessage(),
                        'response_body_preview' => substr($errorBody, 0, 500)
                    ]);
                }
                
                \Log::error('Customer Service Details API Error', [
                    'status_code' => $statusCode,
                    'response_body' => $errorBody,
                    'response_data' => $errorData,
                    'request_data' => $customerServiceData,
                    'headers' => $customerServiceResponse->headers()
                ]);
                
                throw new \Exception('Failed to create customer service details. Status: ' . $statusCode . '. Response: ' . $errorBody);
            }
            
            // Debug: Log the customer service response to see the structure
            $customerServiceResponseData = null;
            $responseBody = $customerServiceResponse->body();
            $statusCode = $customerServiceResponse->status();
            
            \Log::info('Raw API Response', [
                'status_code' => $statusCode,
                'response_body_preview' => substr($responseBody, 0, 1000),
                'content_type' => $customerServiceResponse->header('Content-Type'),
                'response_length' => strlen($responseBody)
            ]);
            
            try {
                $customerServiceResponseData = $customerServiceResponse->json();
            } catch (\Exception $e) {
                \Log::error('Could not parse customer service response as JSON', [
                    'error' => $e->getMessage(),
                    'response_body' => $responseBody,
                    'status_code' => $statusCode,
                    'content_type' => $customerServiceResponse->header('Content-Type')
                ]);
                throw new \Exception('Invalid response format from customer service API: ' . $e->getMessage() . '. Response: ' . substr($responseBody, 0, 500));
            }
            \Log::info('Customer Service Response', [
                'status_code' => $customerServiceResponse->status(),
                'response_data' => $customerServiceResponseData,
                'response_keys' => array_keys($customerServiceResponseData ?? [])
            ]);
            
            $customerServiceDetailsId = $customerServiceResponseData['id'] ?? $customerServiceResponseData['data']['id'] ?? $customerServiceResponseData['customer_service_details_id'] ?? null;
            
            \Log::info('Extracted Customer Service Details ID', [
                'customer_service_details_id' => $customerServiceDetailsId
            ]);
            
            // Validate that we have the customer service details ID
            if (!$customerServiceDetailsId) {
                throw new \Exception('Failed to extract customer service details ID from response. Response: ' . json_encode($customerServiceResponseData));
            }
            
            // Step 2: Create Transaction
            $transactionData = [
                'network_service_id' => $this->selectedServiceId,
                'business_id' => $this->businessId,
                'customer_service_details_id' => $customerServiceDetailsId,
                'network_id' => $this->serviceData['network_service']['network']['id'] ?? null,
                'service_id' => $this->serviceData['network_service']['service']['id'] ?? null,
                'cost_price' => $this->serviceData['cost_price'] ?? 0,
                'selling_price' => $this->serviceData['selling_price'] ?? 0,
                'profit' => ($this->serviceData['selling_price'] ?? 0) - ($this->serviceData['cost_price'] ?? 0),
                'transaction_status' => 'completed',
                'transaction_notes' => 'Service executed via dashboard',
                'is_active' => true,
                'is_deleted' => false,
            ];
            
            $transactionRequest = new TransactionRequest($transactionData, $token);
            $transactionResponse = $connector->send($transactionRequest);
            
            $transactionStatusCode = $transactionResponse->status();
            if ($transactionStatusCode < 200 || $transactionStatusCode >= 300) {
                throw new \Exception('Failed to create transaction. Status: ' . $transactionStatusCode . '. Response: ' . $transactionResponse->body());
            }
            
            // Debug: Log the transaction response to see the structure
            $transactionResponseData = $transactionResponse->json();
            \Log::info('Transaction Response', [
                'status_code' => $transactionResponse->status(),
                'response_data' => $transactionResponseData,
                'response_keys' => array_keys($transactionResponseData ?? [])
            ]);
            
            $transactionId = $transactionResponseData['id'] ?? $transactionResponseData['data']['id'] ?? $transactionResponseData['transaction_id'] ?? null;
            
            \Log::info('Extracted Transaction ID', [
                'transaction_id' => $transactionId
            ]);
            
            // Validate that we have the transaction ID
            if (!$transactionId) {
                throw new \Exception('Failed to extract transaction ID from response. Response: ' . json_encode($transactionResponseData));
            }
            
            // Step 3: Create Payment Details
            $paymentData = [
                'transaction_id' => $transactionId,
                'payment_method' => 'cash', // Default to cash, could be made configurable
                'payment_amount' => $this->serviceData['selling_price'] ?? 0,
                'paid_amount' => $this->serviceData['selling_price'] ?? 0,
                'due_amount' => 0,
                'payment_date' => now()->toDateString(),
                'payment_notes' => 'Payment completed for service execution',
                'payment_status' => 'completed',
                'business_id' => $this->businessId,
            ];
            
            $paymentRequest = new PaymentDetailsRequest($paymentData, $token);
            $paymentResponse = $connector->send($paymentRequest);
            
            $paymentStatusCode = $paymentResponse->status();
            if ($paymentStatusCode < 200 || $paymentStatusCode >= 300) {
                throw new \Exception('Failed to create payment details. Status: ' . $paymentStatusCode . '. Response: ' . $paymentResponse->body());
            }
            
            // Step 4: Update SIM card status to sold (if using new SIM)
            if ($this->isNewSim == '1') {
                $this->updateSimCardSold();
            }
            
            // All requests successful
            $this->isExecuting = false;
            session()->flash('success', 'Service executed successfully! Customer service details, transaction, and payment have been created.');
        return redirect()->route('dashboard.networks');
            
        } catch (\Exception $e) {
            // Reset loading state on error
            $this->isExecuting = false;
            // Log the complete error for debugging
            \Log::error('Service Execution Error', [
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'customer_service_data' => $customerServiceData ?? null,
                'transaction_data' => $transactionData ?? null,
                'payment_data' => $paymentData ?? null,
            ]);
            
            // Handle any errors during the process
            $this->addError('confirmDetails', 'Service execution failed: ' . $e->getMessage());
            session()->flash('error', 'Service execution failed: ' . $e->getMessage());
        }
    }
    
    public function goBack() {
        return redirect()->route('dashboard.networks');
    }
    
    public function updatedIsNewSim() {
        // Reset SIM verification status when SIM choice changes
        $this->simVerificationStatus = null;
        $this->simVerificationMessage = '';
        $this->simSerial = '';
        
        // Clear any existing errors for simSerial when switching away from new SIM
        if ($this->isNewSim == '0') {
            $this->resetErrorBag(['simSerial']);
        }
    }
    
    public function verifySimCard() {
        // Only verify if using a new SIM
        if ($this->isNewSim != '1') {
            return;
        }
        
        $this->validate([
            'simSerial' => 'required|string|min:10',
        ]);
        
        if (!$this->businessId) {
            $this->simVerificationStatus = 'failed';
            $this->simVerificationMessage = 'No business selected. Please select a business first.';
            return;
        }
        
        $this->simVerificationStatus = 'verifying';
        $this->simVerificationMessage = '';
        
        try {
            $networkService = new NetworkService(new TelconApiConnector());
            $response = $networkService->verifySimCard($this->simSerial, $this->businessId);
          
            if ($response->isSuccessful()) {
                if ($response->isValid()) {
                    // Check if SIM is available for use
                    if ($response->isAvailable() === true) {
                        // Store stock_item_id in session if available
                        if ($response->getStockItemId()) {
                            session(['verified_sim_stock_item_id' => $response->getStockItemId()]);
                        }
                        // Set SIM card status to inactive
                        $this->setSimCardInactive();
                        
                        $this->simVerificationStatus = 'verified';
                        $this->simVerificationMessage = 'SIM card verified and available for use';
                    } elseif ($response->isAvailable() === false) {
                        $this->simVerificationStatus = 'failed';
                        $this->simVerificationMessage = $response->getDisplayMessage();
                    } else {
                        // Fallback if availability status is not provided
                        // Store stock_item_id in session if available
                        if ($response->getStockItemId()) {
                            session(['verified_sim_stock_item_id' => $response->getStockItemId()]);
                        }
                        
                        // Set SIM card status to inactive
                        $this->setSimCardInactive();
                        
                        $this->simVerificationStatus = 'verified';
                        $this->simVerificationMessage = $response->getDisplayMessage();
                    }
                } else {
                    $this->simVerificationStatus = 'failed';
                    $this->simVerificationMessage = $response->getDisplayMessage();
                }
            } else {
                $this->simVerificationStatus = 'failed';
                $this->simVerificationMessage = $response->getMessage() ?? 'Verification failed';
            }
            
        } catch (\Exception $e) {
            $this->simVerificationStatus = 'failed';
            $this->simVerificationMessage = 'Verification failed: ' . $e->getMessage();
        }
    }

    private function setSimCardInactive() {
        try {
            $stockItemId = session('verified_sim_stock_item_id');
            
            if (!$stockItemId) {
                \Log::warning('No stock_item_id found in session for SIM card status update');
                return;
            }
            
            $connector = new TelconApiConnector();
            $token = session('auth_token');
            
            if (!$token) {
                \Log::error('No authentication token found for SIM card status update');
                return;
            }
            
            $request = new UpdateSimCardStatusRequest(
                token: $token,
                itemId: $stockItemId,
                status: 'is_active',
                value: false
            );
            
            $response = $connector->send($request);
            
            if ($response->successful()) {
                \Log::info('SIM card status updated to inactive', [
                    'stock_item_id' => $stockItemId,
                    'status' => 'is_active',
                    'value' => false
                ]);
            } else {
                \Log::error('Failed to update SIM card status', [
                    'stock_item_id' => $stockItemId,
                    'status_code' => $response->status(),
                    'response' => $response->body()
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::error('Exception updating SIM card status', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function updateSimCardSold() {
        try {
            $stockItemId = session('verified_sim_stock_item_id');
            
            if (!$stockItemId) {
                \Log::warning('No stock_item_id found in session for SIM card sold status update');
                return;
            }
            
            $connector = new TelconApiConnector();
            $token = session('auth_token');
            
            if (!$token) {
                \Log::error('No authentication token found for SIM card sold status update');
                return;
            }
            
            $request = new UpdateSimCardStatusRequest(
                token: $token,
                itemId: $stockItemId,
                status: 'is_sold',
                value: true
            );
            
            $response = $connector->send($request);
            
            if ($response->successful()) {
                \Log::info('SIM card status updated to sold', [
                    'stock_item_id' => $stockItemId,
                    'status' => 'is_sold',
                    'value' => true
                ]);
                
                // Clear the session after successful update
                session()->forget('verified_sim_stock_item_id');
            } else {
                \Log::error('Failed to update SIM card sold status', [
                    'stock_item_id' => $stockItemId,
                    'status_code' => $response->status(),
                    'response' => $response->body()
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::error('Exception updating SIM card sold status', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}; ?>

<div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Fixed Header -->
    <div class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-10">
        <div class="px-3 sm:px-6 lg:px-8">
            <div class="py-3 sm:py-4">
                <div class="flex items-center">
                    <button wire:click="goBack" class="mr-3 sm:mr-4 p-2 text-gray-400 hover:text-gray-600 transition-colors rounded-lg hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                    <div class="min-w-0 flex-1">
                        <h1 class="text-lg sm:text-xl font-semibold text-gray-900 truncate">Perform Service</h1>
                        <p class="text-xs sm:text-sm text-gray-500 truncate">Execute the selected network service</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scrollable Content -->
    <div class="flex-1 overflow-y-auto">
        <div class="max-w-4xl mx-auto px-3 sm:px-6 lg:px-8 py-4 sm:py-6">
        @if($serviceError)
            <!-- Error State -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 sm:p-6">
                <div class="flex items-start">
                    <svg class="w-6 h-6 sm:w-8 sm:h-8 text-red-400 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-base sm:text-lg font-medium text-red-800">Error Loading Service</h3>
                        <p class="text-sm sm:text-base text-red-600 mt-1 break-words">{{ $serviceError }}</p>
                    </div>
                </div>
                <div class="mt-4">
                    <button wire:click="goBack" class="w-full sm:w-auto bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition-colors text-sm sm:text-base">
                        Go Back
                    </button>
                </div>
            </div>
        @elseif($serviceLoaded && $serviceData)
            <!-- Service Details -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <!-- Service Header -->
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-4 sm:px-6 py-4 sm:py-6 border-b border-gray-100">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
                        <div class="flex items-center">
                            <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl flex items-center justify-center mr-3 sm:mr-4 shadow-sm flex-shrink-0" style="background-color: {{ $serviceData['network_service']['network']['color_code'] ?? '#6B7280' }}">
                                <svg class="w-6 h-6 sm:w-7 sm:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h2 class="text-lg sm:text-2xl font-bold text-gray-900 truncate">{{ $serviceData['network_service']['service']['name'] ?? 'Service' }}</h2>
                                <p class="text-xs sm:text-sm text-gray-600 mt-1 truncate">{{ $serviceData['network_service']['network']['name'] ?? 'Network' }} Network</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between sm:block sm:text-right">
                            <div class="text-2xl sm:text-3xl font-bold text-green-600">₵{{ number_format($serviceData['selling_price'], 2) }}</div>
                            <span class="inline-flex items-center px-2 sm:px-3 py-1 rounded-full text-xs font-medium {{ $serviceData['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $serviceData['is_active'] ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Form Content -->
                <div class="px-4 sm:px-6 py-4 sm:py-6">
                    @if(isset($serviceData['network_service']['service']['description']) && $serviceData['network_service']['service']['description'])
                    <div class="mb-4 sm:mb-6 p-3 sm:p-4 bg-gray-50 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Description</h3>
                        <p class="text-gray-600 text-sm leading-relaxed">{{ $serviceData['network_service']['service']['description'] }}</p>
                    </div>
                    @endif

                    <!-- 4-Step Service Execution Form -->
                    <!-- Progress Steps -->
                    <div class="mb-6 sm:mb-8 p-4 sm:p-6 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-100">
                        <!-- Mobile Progress Steps -->
                        <div class="block sm:hidden">
                            <div class="flex items-center justify-center mb-4">
                                <div class="flex items-center justify-center w-8 h-8 rounded-full {{ $currentStep >= 1 ? 'bg-blue-600 text-white shadow-lg' : 'bg-white text-gray-400 border-2 border-gray-200' }}">
                                    <span class="text-xs font-semibold">1</span>
                                </div>
                                <div class="flex-1 h-1 mx-2 rounded-full {{ $currentStep > 1 ? 'bg-blue-600' : 'bg-gray-200' }}"></div>
                                <div class="flex items-center justify-center w-8 h-8 rounded-full {{ $currentStep >= 2 ? 'bg-blue-600 text-white shadow-lg' : 'bg-white text-gray-400 border-2 border-gray-200' }}">
                                    <span class="text-xs font-semibold">2</span>
                                </div>
                                <div class="flex-1 h-1 mx-2 rounded-full {{ $currentStep > 2 ? 'bg-blue-600' : 'bg-gray-200' }}"></div>
                                <div class="flex items-center justify-center w-8 h-8 rounded-full {{ $currentStep >= 3 ? 'bg-blue-600 text-white shadow-lg' : 'bg-white text-gray-400 border-2 border-gray-200' }}">
                                    <span class="text-xs font-semibold">3</span>
                                </div>
                                <div class="flex-1 h-1 mx-2 rounded-full {{ $currentStep > 3 ? 'bg-blue-600' : 'bg-gray-200' }}"></div>
                                <div class="flex items-center justify-center w-8 h-8 rounded-full {{ $currentStep >= 4 ? 'bg-blue-600 text-white shadow-lg' : 'bg-white text-gray-400 border-2 border-gray-200' }}">
                                    <span class="text-xs font-semibold">4</span>
                                </div>
                            </div>
                            <div class="text-center">
                                <span class="text-sm font-semibold {{ $currentStep >= 1 ? 'text-blue-700' : 'text-gray-500' }}">
                                    @if($currentStep == 1) SIM Setup
                                    @elseif($currentStep == 2) Customer Details
                                    @elseif($currentStep == 3) ID & Photo
                                    @elseif($currentStep == 4) Confirmation
                                    @endif
                                </span>
                            </div>
                        </div>
                        
                        <!-- Desktop Progress Steps -->
                        <div class="hidden sm:flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="flex items-center justify-center w-10 h-10 rounded-full {{ $currentStep >= 1 ? 'bg-blue-600 text-white shadow-lg' : 'bg-white text-gray-400 border-2 border-gray-200' }}">
                                    <span class="text-sm font-semibold">1</span>
                                </div>
                                <span class="ml-3 text-sm font-semibold {{ $currentStep >= 1 ? 'text-blue-700' : 'text-gray-500' }}">SIM Setup</span>
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
                        <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-6 shadow-sm">
                            <h3 class="text-lg sm:text-xl font-semibold text-gray-900 mb-4 sm:mb-6 flex items-center">
                                <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-2 sm:mr-3 flex-shrink-0">
                                    <svg class="w-3 h-3 sm:w-4 sm:h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <span class="truncate">SIM Card Setup</span>
                            </h3>
                            <div class="space-y-4 sm:space-y-6">
                                <!-- SIM Type Selection -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">Are you using a new SIM card?</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                                        <label class="relative cursor-pointer group">
                                            <input type="radio" wire:model.live="isNewSim" value="1" class="sr-only">
                                            <div class="p-3 sm:p-4 border-2 rounded-xl transition-all duration-300 transform group-hover:scale-[1.02] group-active:scale-[0.98] {{ $isNewSim == '1' ? 'border-blue-500 bg-blue-50 shadow-lg shadow-blue-100' : 'border-gray-200 hover:border-blue-300 hover:bg-blue-25 hover:shadow-md' }}">
                                                <div class="flex items-center">
                                                    <div class="w-4 h-4 sm:w-5 sm:h-5 rounded-full border-2 mr-2 sm:mr-3 flex items-center justify-center transition-all duration-300 flex-shrink-0 {{ $isNewSim == '1' ? 'border-blue-500 bg-blue-500 shadow-sm' : 'border-gray-300 group-hover:border-blue-400' }}">
                                                        @if($isNewSim == '1')
                                                            <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-white rounded-full transition-all duration-200"></div>
                                                        @endif
                                                    </div>
                                                    <div class="min-w-0 flex-1">
                                                        <div class="text-sm sm:text-base font-medium transition-colors duration-200 {{ $isNewSim == '1' ? 'text-blue-900' : 'text-gray-900 group-hover:text-blue-800' }}">New SIM Card</div>
                                                        <div class="text-xs sm:text-sm transition-colors duration-200 {{ $isNewSim == '1' ? 'text-blue-600' : 'text-gray-500 group-hover:text-blue-500' }} leading-tight">I have a new SIM card that needs verification</div>
                                                    </div>
                                                </div>
                                                <!-- Selection indicator -->
                                                @if($isNewSim == '1')
                                                    <div class="absolute top-2 right-2 w-5 h-5 sm:w-6 sm:h-6 bg-blue-500 rounded-full flex items-center justify-center transition-all duration-300">
                                                        <svg class="w-2.5 h-2.5 sm:w-3 sm:h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>
                                        </label>
                                        <label class="relative cursor-pointer group">
                                            <input type="radio" wire:model.live="isNewSim" value="0" class="sr-only">
                                            <div class="p-3 sm:p-4 border-2 rounded-xl transition-all duration-300 transform group-hover:scale-[1.02] group-active:scale-[0.98] {{ $isNewSim == '0' ? 'border-blue-500 bg-blue-50 shadow-lg shadow-blue-100' : 'border-gray-200 hover:border-blue-300 hover:bg-blue-25 hover:shadow-md' }}">
                                                <div class="flex items-center">
                                                    <div class="w-4 h-4 sm:w-5 sm:h-5 rounded-full border-2 mr-2 sm:mr-3 flex items-center justify-center transition-all duration-300 flex-shrink-0 {{ $isNewSim == '0' ? 'border-blue-500 bg-blue-500 shadow-sm' : 'border-gray-300 group-hover:border-blue-400' }}">
                                                        @if($isNewSim == '0')
                                                            <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-white rounded-full transition-all duration-200"></div>
                                                        @endif
                                                    </div>
                                                    <div class="min-w-0 flex-1">
                                                        <div class="text-sm sm:text-base font-medium transition-colors duration-200 {{ $isNewSim == '0' ? 'text-blue-900' : 'text-gray-900 group-hover:text-blue-800' }}">Existing SIM Card</div>
                                                        <div class="text-xs sm:text-sm transition-colors duration-200 {{ $isNewSim == '0' ? 'text-blue-600' : 'text-gray-500 group-hover:text-blue-500' }} leading-tight">I'm using an existing SIM card</div>
                                                    </div>
                                                </div>
                                                <!-- Selection indicator -->
                                                @if($isNewSim == '0')
                                                    <div class="absolute top-2 right-2 w-5 h-5 sm:w-6 sm:h-6 bg-blue-500 rounded-full flex items-center justify-center transition-all duration-300">
                                                        <svg class="w-2.5 h-2.5 sm:w-3 sm:h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>
                                        </label>
                                    </div>
                                    @error('isNewSim') <span class="text-red-500 text-sm mt-2 block">{{ $message }}</span> @enderror
                                </div>

                                <!-- Conditional SIM Serial Input (only for new SIM) -->
                                @if($isNewSim == '1')
                                <div class="transition-all duration-300 ease-in-out">
                                    <label for="sim_serial" class="block text-sm font-semibold text-gray-700 mb-2">SIM Card Serial Number</label>
                                    <div class="flex gap-3">
                                        <input type="text" id="sim_serial" wire:model="simSerial" 
                                               class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                               placeholder="Enter SIM card serial number" required>
                                        <button type="button" wire:click="verifySimCard" 
                                                class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                                                @if($simVerificationStatus === 'verifying') disabled @endif>
                                            @if($simVerificationStatus === 'verifying')
                                                <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                            @else
                                                Verify
                                            @endif
                                        </button>
                                    </div>
                                    @error('simSerial') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                                   
                                    <!-- Help text for SIM verification requirement -->
                                    <div class="mt-2 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 text-amber-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                            </svg>
                                            <span class="text-sm font-medium text-amber-800">SIM card must be verified before proceeding to the next step.</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Verification Result -->
                                    @if($simVerificationStatus)
                                        <div class="mt-3 p-3 rounded-lg border
                                            @if($simVerificationStatus === 'verified') bg-green-50 border-green-200 @endif
                                            @if($simVerificationStatus === 'failed') bg-red-50 border-red-200 @endif
                                            @if($simVerificationStatus === 'verifying') bg-blue-50 border-blue-200 @endif">
                                            <div class="flex items-center">
                                                @if($simVerificationStatus === 'verified')
                                                    <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <span class="text-sm font-medium text-green-800">{{ $simVerificationMessage }}</span>
                                                @elseif($simVerificationStatus === 'failed')
                                                    <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <span class="text-sm font-medium text-red-800">{{ $simVerificationMessage }}</span>
                                                @elseif($simVerificationStatus === 'verifying')
                                                    <svg class="w-5 h-5 text-blue-600 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                    </svg>
                                                    <span class="text-sm font-medium text-blue-800">Verifying SIM card...</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                @endif
                                <!-- Phone Number (always required) -->
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
                        <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-6 shadow-sm">
                            <h3 class="text-lg sm:text-xl font-semibold text-gray-900 mb-4 sm:mb-6 flex items-center">
                                <div class="w-7 h-7 sm:w-8 sm:h-8 bg-green-100 rounded-lg flex items-center justify-center mr-2 sm:mr-3 flex-shrink-0">
                                    <svg class="w-3 h-3 sm:w-4 sm:h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                                <span class="truncate">Customer Details</span>
                            </h3>
                            <div class="space-y-4 sm:space-y-6">
                                <!-- Basic Information -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                                    <div>
                                        <label for="first_name" class="block text-sm font-semibold text-gray-700 mb-2">First Name *</label>
                                        <input type="text" id="first_name" wire:model="firstName" 
                                               class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors text-sm sm:text-base"
                                               placeholder="Enter first name" required>
                                        @error('firstName') <span class="text-red-500 text-xs sm:text-sm mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="last_name" class="block text-sm font-semibold text-gray-700 mb-2">Last Name *</label>
                                        <input type="text" id="last_name" wire:model="lastName" 
                                               class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors text-sm sm:text-base"
                                               placeholder="Enter last name" required>
                                        @error('lastName') <span class="text-red-500 text-xs sm:text-sm mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                                <div>
                                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
                                    <input type="email" id="email" wire:model="email" 
                                               class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors text-sm sm:text-base"
                                           placeholder="Enter email address">
                                        @error('email') <span class="text-red-500 text-xs sm:text-sm mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                    <div>
                                        <label for="location" class="block text-sm font-semibold text-gray-700 mb-2">Location</label>
                                        <input type="text" id="location" wire:model="location" 
                                               class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors text-sm sm:text-base"
                                               placeholder="Enter location">
                                        @error('location') <span class="text-red-500 text-xs sm:text-sm mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div>
                                    <label for="address" class="block text-sm font-semibold text-gray-700 mb-2">Address</label>
                                    <textarea id="address" wire:model="address" rows="3"
                                              class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors resize-none text-sm sm:text-base"
                                              placeholder="Enter customer address"></textarea>
                                    @error('address') <span class="text-red-500 text-xs sm:text-sm mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <!-- Next of Kin Information -->
                                <div class="border-t border-gray-200 pt-4 sm:pt-6">
                                    <h4 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">Next of Kin Information</h4>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                                        <div>
                                            <label for="NOK_name" class="block text-sm font-semibold text-gray-700 mb-2">NOK Name</label>
                                            <input type="text" id="NOK_name" wire:model="NOK_name" 
                                                   class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors text-sm sm:text-base"
                                                   placeholder="Enter next of kin name">
                                            @error('NOK_name') <span class="text-red-500 text-xs sm:text-sm mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="NOK_phone" class="block text-sm font-semibold text-gray-700 mb-2">NOK Phone</label>
                                            <input type="tel" id="NOK_phone" wire:model="NOK_phone" 
                                                   class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors text-sm sm:text-base"
                                                   placeholder="Enter NOK phone number">
                                            @error('NOK_phone') <span class="text-red-500 text-xs sm:text-sm mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>
                                <!-- Additional Contact Information -->
                                <div class="border-t border-gray-200 pt-4 sm:pt-6">
                                    <h4 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">Additional Information</h4>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                                        <div>
                                            <label for="Alternate_phone_number" class="block text-sm font-semibold text-gray-700 mb-2">Alternate Phone Number</label>
                                            <input type="tel" id="Alternate_phone_number" wire:model="Alternate_phone_number" 
                                                   class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors text-sm sm:text-base"
                                                   placeholder="Enter alternate phone number">
                                            @error('Alternate_phone_number') <span class="text-red-500 text-xs sm:text-sm mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Activation Status -->
                                <div class="border-t border-gray-200 pt-4 sm:pt-6">
                                    <h4 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">Activation Status <span class="text-gray-400 font-normal">(Optional)</span></h4>
                                    <p class="text-xs sm:text-sm text-gray-600 mb-4">Check the services that are currently active (all optional):</p>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                                        <div class="flex items-center p-3 sm:p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                            <input type="checkbox" id="MyMTNApp_Activation_Status" wire:model="MyMTNApp_Activation_Status" 
                                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
                                            <label for="MyMTNApp_Activation_Status" class="ml-3 text-sm sm:text-base font-medium text-gray-700 cursor-pointer">
                                                MyMTN App
                                            </label>
                                        </div>
                                        <div class="flex items-center p-3 sm:p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                            <input type="checkbox" id="MomoApp_Activation_Status" wire:model="MomoApp_Activation_Status" 
                                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
                                            <label for="MomoApp_Activation_Status" class="ml-3 text-sm sm:text-base font-medium text-gray-700 cursor-pointer">
                                                Momo App
                                            </label>
                                        </div>
                                        <div class="flex items-center p-3 sm:p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                            <input type="checkbox" id="ADS_Activation_Status" wire:model="ADS_Activation_Status" 
                                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
                                            <label for="ADS_Activation_Status" class="ml-3 text-sm sm:text-base font-medium text-gray-700 cursor-pointer">
                                                ADS
                                            </label>
                                        </div>
                                        <div class="flex items-center p-3 sm:p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                            <input type="checkbox" id="RGT_Activation_Status" wire:model="RGT_Activation_Status" 
                                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
                                            <label for="RGT_Activation_Status" class="ml-3 text-sm sm:text-base font-medium text-gray-700 cursor-pointer">
                                                RGT
                                            </label>
                                        </div>
                                    </div>
                                    @error('MyMTNApp_Activation_Status') <span class="text-red-500 text-xs sm:text-sm mt-1 block">{{ $message }}</span> @enderror
                                    @error('MomoApp_Activation_Status') <span class="text-red-500 text-xs sm:text-sm mt-1 block">{{ $message }}</span> @enderror
                                    @error('ADS_Activation_Status') <span class="text-red-500 text-xs sm:text-sm mt-1 block">{{ $message }}</span> @enderror
                                    @error('RGT_Activation_Status') <span class="text-red-500 text-xs sm:text-sm mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <!-- Additional Details -->
                                <div class="border-t border-gray-200 pt-4 sm:pt-6">
                                    <h4 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">Additional Details</h4>
                                    <div class="space-y-4 sm:space-y-6">
                                        <div>
                                            <label for="Remarks" class="block text-sm font-semibold text-gray-700 mb-2">Remarks</label>
                                            <textarea id="Remarks" wire:model="Remarks" rows="3"
                                                      class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors resize-none text-sm sm:text-base"
                                                      placeholder="Enter any remarks"></textarea>
                                            @error('Remarks') <span class="text-red-500 text-xs sm:text-sm mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                                            <div>
                                                <label for="Reason_for_Action" class="block text-sm font-semibold text-gray-700 mb-2">Reason for Action</label>
                                                <input type="text" id="Reason_for_Action" wire:model="Reason_for_Action" 
                                                       class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors text-sm sm:text-base"
                                                       placeholder="Enter reason for action">
                                                @error('Reason_for_Action') <span class="text-red-500 text-xs sm:text-sm mt-1 block">{{ $message }}</span> @enderror
                                            </div>
                                            <div>
                                                <label for="Ticket_Number" class="block text-sm font-semibold text-gray-700 mb-2">Ticket Number</label>
                                                <input type="text" id="Ticket_Number" wire:model="Ticket_Number" 
                                                       class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors text-sm sm:text-base"
                                                       placeholder="Enter ticket number">
                                                @error('Ticket_Number') <span class="text-red-500 text-xs sm:text-sm mt-1 block">{{ $message }}</span> @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Step 3: ID and Document Upload -->
                        @if($currentStep == 3)
                        <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-6 shadow-sm">
                            <h3 class="text-lg sm:text-xl font-semibold text-gray-900 mb-4 sm:mb-6 flex items-center">
                                <div class="w-7 h-7 sm:w-8 sm:h-8 bg-purple-100 rounded-lg flex items-center justify-center mr-2 sm:mr-3 flex-shrink-0">
                                    <svg class="w-3 h-3 sm:w-4 sm:h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <span class="truncate">ID & Document Upload</span>
                            </h3>
                            
                            <!-- Optional Notice -->
                            <div class="mb-4 sm:mb-6 p-3 sm:p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                <div class="flex items-start">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-blue-600 mr-2 sm:mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                <div>
                                        <p class="text-sm font-medium text-blue-800">All uploads are optional</p>
                                        <p class="text-xs sm:text-sm text-blue-600 mt-1">You can skip any of these uploads if not required for your service.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                                <!-- Front ID -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2 sm:mb-3">Front ID <span class="text-gray-400 font-normal">(Optional)</span></label>
                                    <div class="border-2 border-dashed border-gray-300 rounded-xl p-4 sm:p-6 sm:p-8 text-center hover:border-blue-400 transition-colors">
                                        <input type="file" wire:model="frontId" accept="image/*" class="hidden" id="front-id">
                                        <label for="front-id" class="cursor-pointer">
                                            @if($frontId)
                                                <img src="{{ $frontId->temporaryUrl() }}" alt="Front ID" class="mx-auto h-32 sm:h-40 w-auto rounded-lg shadow-sm">
                                                <p class="mt-2 sm:mt-3 text-xs sm:text-sm font-medium text-green-600">✓ Front ID uploaded</p>
                                            @else
                                                <svg class="mx-auto h-12 w-12 sm:h-16 sm:w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                                <p class="mt-2 sm:mt-3 text-xs sm:text-sm font-medium text-gray-600">Click to upload front ID</p>
                                                <p class="text-xs text-gray-500 mt-1">Max 2MB, JPG/PNG</p>
                                            @endif
                                        </label>
                                    </div>
                                    @error('frontId') <span class="text-red-500 text-xs sm:text-sm mt-1 sm:mt-2 block">{{ $message }}</span> @enderror
                                </div>

                                <!-- Back ID -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2 sm:mb-3">Back ID <span class="text-gray-400 font-normal">(Optional)</span></label>
                                    <div class="border-2 border-dashed border-gray-300 rounded-xl p-4 sm:p-6 sm:p-8 text-center hover:border-blue-400 transition-colors">
                                        <input type="file" wire:model="backId" accept="image/*" class="hidden" id="back-id">
                                        <label for="back-id" class="cursor-pointer">
                                            @if($backId)
                                                <img src="{{ $backId->temporaryUrl() }}" alt="Back ID" class="mx-auto h-32 sm:h-40 w-auto rounded-lg shadow-sm">
                                                <p class="mt-2 sm:mt-3 text-xs sm:text-sm font-medium text-green-600">✓ Back ID uploaded</p>
                                            @else
                                                <svg class="mx-auto h-12 w-12 sm:h-16 sm:w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                                <p class="mt-2 sm:mt-3 text-xs sm:text-sm font-medium text-gray-600">Click to upload back ID</p>
                                                <p class="text-xs text-gray-500 mt-1">Max 2MB, JPG/PNG</p>
                                            @endif
                                        </label>
                                    </div>
                                    @error('backId') <span class="text-red-500 text-xs sm:text-sm mt-1 sm:mt-2 block">{{ $message }}</span> @enderror
                                </div>

                                <!-- Screenshots -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2 sm:mb-3">Screenshots <span class="text-gray-400 font-normal">(Optional)</span></label>
                                    <div class="border-2 border-dashed border-gray-300 rounded-xl p-4 sm:p-6 sm:p-8 text-center hover:border-blue-400 transition-colors">
                                        <input type="file" wire:model="screenshots" accept="image/*" class="hidden" id="screenshots">
                                        <label for="screenshots" class="cursor-pointer">
                                            @if($screenshots)
                                                <img src="{{ $screenshots->temporaryUrl() }}" alt="Screenshots" class="mx-auto h-32 sm:h-40 w-auto rounded-lg shadow-sm">
                                                <p class="mt-2 sm:mt-3 text-xs sm:text-sm font-medium text-green-600">✓ Screenshots uploaded</p>
                                            @else
                                                <svg class="mx-auto h-12 w-12 sm:h-16 sm:w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                <p class="mt-2 sm:mt-3 text-xs sm:text-sm font-medium text-gray-600">Click to upload screenshots</p>
                                                <p class="text-xs text-gray-500 mt-1">Max 2MB, JPG/PNG</p>
                                            @endif
                                        </label>
                            </div>
                                    @error('screenshots') <span class="text-red-500 text-xs sm:text-sm mt-1 sm:mt-2 block">{{ $message }}</span> @enderror
                                </div>

                                <!-- Document -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2 sm:mb-3">Document <span class="text-gray-400 font-normal">(Optional)</span></label>
                                    <div class="border-2 border-dashed border-gray-300 rounded-xl p-4 sm:p-6 sm:p-8 text-center hover:border-blue-400 transition-colors">
                                        <input type="file" wire:model="document" accept="image/*" class="hidden" id="document">
                                        <label for="document" class="cursor-pointer">
                                            @if($document)
                                                <img src="{{ $document->temporaryUrl() }}" alt="Document" class="mx-auto h-32 sm:h-40 w-auto rounded-lg shadow-sm">
                                                <p class="mt-2 sm:mt-3 text-xs sm:text-sm font-medium text-green-600">✓ Document uploaded</p>
                                            @else
                                                <svg class="mx-auto h-12 w-12 sm:h-16 sm:w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                <p class="mt-2 sm:mt-3 text-xs sm:text-sm font-medium text-gray-600">Click to upload document</p>
                                                <p class="text-xs text-gray-500 mt-1">Max 2MB, JPG/PNG</p>
                                            @endif
                                        </label>
                                    </div>
                                    @error('document') <span class="text-red-500 text-xs sm:text-sm mt-1 sm:mt-2 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Step 4: Final Confirmation -->
                        @if($currentStep == 4)
                        <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-6 shadow-sm">
                            <h3 class="text-lg sm:text-xl font-semibold text-gray-900 mb-4 sm:mb-6 flex items-center">
                                <div class="w-7 h-7 sm:w-8 sm:h-8 bg-orange-100 rounded-lg flex items-center justify-center mr-2 sm:mr-3 flex-shrink-0">
                                    <svg class="w-3 h-3 sm:w-4 sm:h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <span class="truncate">Final Confirmation</span>
                            </h3>
                            
                            <!-- Transaction Summary -->
                            <div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-xl p-4 sm:p-6 border border-green-100 mb-4 sm:mb-6">
                                <h4 class="text-base sm:text-lg font-semibold text-gray-900 mb-3 sm:mb-4">Transaction Summary</h4>
                                <div class="space-y-3 sm:space-y-4">
                                    <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                        <span class="text-xs sm:text-sm font-medium text-gray-700">Service:</span>
                                        <span class="text-xs sm:text-sm font-semibold text-gray-900">{{ $serviceData['network_service']['service']['name'] ?? 'Service' }}</span>
                                    </div>
                                    <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                        <span class="text-xs sm:text-sm font-medium text-gray-700">Network:</span>
                                        <span class="text-xs sm:text-sm font-semibold text-gray-900">{{ $serviceData['network_service']['network']['name'] ?? 'Network' }}</span>
                                    </div>
                                    <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                        <span class="text-xs sm:text-sm font-medium text-gray-700">Phone Number:</span>
                                        <span class="text-xs sm:text-sm font-semibold text-gray-900">{{ $phoneNumber }}</span>
                                    </div>
                                    <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                        <span class="text-xs sm:text-sm font-medium text-gray-700">Customer:</span>
                                        <span class="text-xs sm:text-sm font-semibold text-gray-900">{{ $firstName }} {{ $lastName }}</span>
                                    </div>
                                    @if($location)
                                    <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                        <span class="text-xs sm:text-sm font-medium text-gray-700">Location:</span>
                                        <span class="text-xs sm:text-sm font-semibold text-gray-900">{{ $location }}</span>
                                    </div>
                                    @endif
                                    <div class="flex justify-between items-center py-3 bg-white rounded-lg px-3 sm:px-4">
                                        <span class="text-sm sm:text-base font-bold text-gray-900">Total Amount:</span>
                                        <span class="text-xl sm:text-2xl font-bold text-green-600">₵{{ number_format($serviceData['selling_price'], 2) }}</span>
                                </div>
                            </div>
                            </div>

                            <!-- Activation Status Check -->
                            @php
                                $activationChecks = [
                                    'MyMTN App' => $MyMTNApp_Activation_Status,
                                    'Momo App' => $MomoApp_Activation_Status,
                                    'ADS' => $ADS_Activation_Status,
                                    'RGT' => $RGT_Activation_Status,
                                ];
                                $failedActivations = [];
                                $activeActivations = [];
                                foreach ($activationChecks as $service => $status) {
                                    if ($status === true) {
                                        $activeActivations[] = $service;
                                    } else {
                                        $failedActivations[] = $service;
                                    }
                                }
                            @endphp

                            <div class="mb-4 sm:mb-6 p-4 sm:p-6 rounded-xl border {{ !empty($failedActivations) ? 'bg-amber-50 border-amber-200' : 'bg-green-50 border-green-200' }}">
                                <h4 class="text-base sm:text-lg font-semibold text-gray-900 mb-3 sm:mb-4 flex items-center">
                                    @if(!empty($failedActivations))
                                        <svg class="w-5 h-5 text-amber-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                        </svg>
                                        <span class="text-amber-800">Activation Status - Some Inactive</span>
                                    @else
                                        <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span class="text-green-800">Activation Status - All Active</span>
                                    @endif
                                </h4>
                                
                                <div class="space-y-2 sm:space-y-3">
                                    @foreach($activationChecks as $service => $status)
                                        <div class="flex justify-between items-center py-2 px-3 rounded-lg {{ $status ? 'bg-green-100' : 'bg-amber-100' }}">
                                            <span class="text-xs sm:text-sm font-medium text-gray-700">{{ $service }}:</span>
                                            <span class="text-xs sm:text-sm font-semibold {{ $status ? 'text-green-800' : 'text-amber-800' }}">
                                                {{ $status ? 'Active' : 'Inactive' }}
                                                @if($status)
                                                    <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                                    </svg>
                                                @endif
                                            </span>
                                        </div>
                                    @endforeach
                                </div>

                                @if(!empty($failedActivations))
                                <div class="mt-3 sm:mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                                    <div class="flex items-start">
                                        <svg class="w-4 h-4 text-amber-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                        </svg>
                                        <div>
                                            <p class="text-sm font-medium text-amber-800">Note: Some services are inactive</p>
                                            <p class="text-xs text-amber-600 mt-1">The following services are currently inactive: {{ implode(', ', $failedActivations) }}. This will not prevent the transaction from proceeding.</p>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>

                            <!-- Additional Information Summary -->
                            @if($NOK_name || $NOK_phone || $Alternate_phone_number || $Remarks || $Reason_for_Action || $Ticket_Number)
                            <div class="mb-4 sm:mb-6 p-4 sm:p-6 bg-gray-50 rounded-xl border border-gray-200">
                                <h4 class="text-base sm:text-lg font-semibold text-gray-900 mb-3 sm:mb-4">Additional Information</h4>
                                <div class="space-y-2 sm:space-y-3">
                                    @if($NOK_name)
                                    <div class="flex justify-between items-center py-1">
                                        <span class="text-xs sm:text-sm font-medium text-gray-700">NOK Name:</span>
                                        <span class="text-xs sm:text-sm font-semibold text-gray-900">{{ $NOK_name }}</span>
                                    </div>
                                    @endif
                                    @if($NOK_phone)
                                    <div class="flex justify-between items-center py-1">
                                        <span class="text-xs sm:text-sm font-medium text-gray-700">NOK Phone:</span>
                                        <span class="text-xs sm:text-sm font-semibold text-gray-900">{{ $NOK_phone }}</span>
                                    </div>
                                    @endif
                                    @if($Alternate_phone_number)
                                    <div class="flex justify-between items-center py-1">
                                        <span class="text-xs sm:text-sm font-medium text-gray-700">Alternate Phone:</span>
                                        <span class="text-xs sm:text-sm font-semibold text-gray-900">{{ $Alternate_phone_number }}</span>
                                    </div>
                                    @endif
                                    @if($Ticket_Number)
                                    <div class="flex justify-between items-center py-1">
                                        <span class="text-xs sm:text-sm font-medium text-gray-700">Ticket Number:</span>
                                        <span class="text-xs sm:text-sm font-semibold text-gray-900">{{ $Ticket_Number }}</span>
                                    </div>
                                    @endif
                                    @if($Reason_for_Action)
                                    <div class="flex justify-between items-center py-1">
                                        <span class="text-xs sm:text-sm font-medium text-gray-700">Reason for Action:</span>
                                        <span class="text-xs sm:text-sm font-semibold text-gray-900">{{ $Reason_for_Action }}</span>
                                    </div>
                                    @endif
                                    @if($Remarks)
                                    <div class="py-1">
                                        <span class="text-xs sm:text-sm font-medium text-gray-700 block mb-1">Remarks:</span>
                                        <span class="text-xs sm:text-sm font-semibold text-gray-900">{{ $Remarks }}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endif

                            <!-- Confirmation Checkbox -->
                            <div class="p-3 sm:p-4 bg-blue-50 rounded-lg border border-blue-200">
                                <label class="flex items-start">
                                    <input type="checkbox" wire:model="confirmDetails" class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-3 text-xs sm:text-sm text-gray-700">
                                        <span class="font-semibold">I confirm all details are correct</span> and authorize this transaction. By proceeding, I agree to the terms and conditions.
                                    </span>
                                </label>
                                @error('confirmDetails') <span class="text-red-500 text-xs sm:text-sm mt-2 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        @endif

                        <!-- Navigation Buttons -->
                        <div class="pt-6 sm:pt-8 border-t border-gray-200">
                            <!-- Mobile Layout -->
                            <div class="block sm:hidden space-y-3">
                                @if($currentStep < 4)
                                <button type="button" wire:click="nextStep" 
                                        class="w-full flex items-center justify-center px-6 py-3 rounded-lg transition-colors font-semibold shadow-sm
                                               @if($currentStep == 1 && $isNewSim == '1' && $simVerificationStatus !== 'verified')
                                                   bg-gray-400 text-gray-200 cursor-not-allowed
                                               @else
                                                   bg-blue-600 text-white hover:bg-blue-700
                                               @endif"
                                        @if($currentStep == 1 && $isNewSim == '1' && $simVerificationStatus !== 'verified') disabled @endif>
                                    Next
                                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                                @else
                                @php
                                    $failedActivations = [];
                                    $activationChecks = [
                                        'MyMTNApp' => $MyMTNApp_Activation_Status,
                                        'MomoApp' => $MomoApp_Activation_Status,
                                        'ADS' => $ADS_Activation_Status,
                                        'RGT' => $RGT_Activation_Status,
                                    ];
                                    foreach ($activationChecks as $service => $status) {
                                        if ($status === false) {
                                            $failedActivations[] = $service;
                                        }
                                    }
                                @endphp
                                <button type="button" wire:click="executeService" 
                                        class="w-full flex items-center justify-center px-6 py-3 rounded-lg transition-colors font-semibold shadow-sm
                                               @if($isExecuting)
                                                   bg-gray-400 text-gray-200 cursor-not-allowed
                                               @else
                                                   bg-green-600 text-white hover:bg-green-700
                                               @endif"
                                        @if($isExecuting) disabled @endif>
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Execute Service
                                </button>
                                @endif
                                
                                <div class="flex space-x-3">
                                    @if($currentStep > 1)
                                    <button type="button" wire:click="previousStep" 
                                            class="flex-1 flex items-center justify-center px-4 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors font-medium text-sm">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                        </svg>
                                        Previous
                                    </button>
                                    @endif
                                    <button type="button" wire:click="goBack" 
                                            class="flex-1 px-4 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors font-medium text-sm">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Desktop Layout -->
                            <div class="hidden sm:flex justify-between items-center">
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
                                            class="flex items-center px-8 py-3 rounded-lg transition-colors font-semibold shadow-sm
                                                   @if($currentStep == 1 && $isNewSim == '1' && $simVerificationStatus !== 'verified')
                                                       bg-gray-400 text-gray-200 cursor-not-allowed
                                                   @else
                                                       bg-blue-600 text-white hover:bg-blue-700
                                                   @endif"
                                            @if($currentStep == 1 && $isNewSim == '1' && $simVerificationStatus !== 'verified') disabled @endif>
                                    Next
                                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                                @else
                                @php
                                    $failedActivations = [];
                                    $activationChecks = [
                                        'MyMTNApp' => $MyMTNApp_Activation_Status,
                                        'MomoApp' => $MomoApp_Activation_Status,
                                        'ADS' => $ADS_Activation_Status,
                                        'RGT' => $RGT_Activation_Status,
                                    ];
                                    foreach ($activationChecks as $service => $status) {
                                        if ($status === false) {
                                            $failedActivations[] = $service;
                                        }
                                    }
                                @endphp
                                <button type="button" wire:click="executeService" 
                                        class="flex items-center px-8 py-3 rounded-lg transition-colors font-semibold shadow-sm
                                               @if($isExecuting)
                                                   bg-gray-400 text-gray-200 cursor-not-allowed
                                               @else
                                                   bg-green-600 text-white hover:bg-green-700
                                               @endif"
                                        @if($isExecuting) disabled @endif>
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
            
            <!-- Loading Overlay -->
            @if($isExecuting)
            <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-xl p-8 max-w-md mx-4 shadow-2xl">
                    <div class="text-center">
                        <!-- Spinner -->
                        <div class="w-16 h-16 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mx-auto mb-6"></div>
                        
                        <!-- Loading Text -->
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Processing Request</h3>
                        <p class="text-gray-600 mb-6">Please wait while we process your service request...</p>
                        
                        <!-- Progress Steps -->
                        <div class="space-y-3 text-left">
                            <div class="flex items-center text-sm">
                                <div class="w-4 h-4 bg-blue-600 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                                    <svg class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <span class="text-gray-700">Creating customer service details...</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <div class="w-4 h-4 bg-gray-300 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                                    <div class="w-2 h-2 bg-white rounded-full animate-pulse"></div>
                                </div>
                                <span class="text-gray-500">Creating transaction...</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <div class="w-4 h-4 bg-gray-300 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                                    <div class="w-2 h-2 bg-white rounded-full animate-pulse"></div>
                                </div>
                                <span class="text-gray-500">Creating payment details...</span>
                            </div>
                        </div>
                        
                        <!-- Warning Message -->
                        <div class="mt-6 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 text-amber-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                <span class="text-xs text-amber-800">Please do not close this window or refresh the page</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
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
