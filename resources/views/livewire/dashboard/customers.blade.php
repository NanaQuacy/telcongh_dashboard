<?php

use Livewire\Volt\Component;
use App\Http\Integrations\TelconApiConnector;
use App\Http\Integrations\Requests\GetCustomerServiceDetailsRequest;
use App\Http\Integrations\Requests\DownloadCustomerServiceDetailsExcelRequest;
use App\Http\Integrations\Requests\DownloadCustomerServiceDetailsPdfRequest;
use App\Http\Integrations\Requests\DownloadCustomerServiceDetailsCsvRequest;

new class extends Component {
    public $customers = [];
    public $loading = true;
    public $error = null;
    public $businessId;
    public $filters = [
        'search' => '',
        'limit' => 20,
        'page' => 1,
        'sort_by' => 'created_at',
        'sort_order' => 'desc'
    ];
    public $pagination = [];
    public $totalCustomers = 0;

    // Modal properties
    public $showCustomerModal = false;
    public $selectedCustomerData = null;

    // Download properties
    public $downloading = false;
    public $downloadError = null;

    public function mount()
    {
        $this->businessId = session('selected_business')['id'];
        $this->loadCustomers();
    }

    public function loadCustomers()
    {
        try {
            $this->loading = true;
            $this->error = null;

            $connector = new TelconApiConnector();
            $token = session('auth_token');

            if (!$token) {
                $this->error = 'Authentication token not found. Please login again.';
                $this->loading = false;
                return;
            }

            $request = new GetCustomerServiceDetailsRequest($this->businessId, $token);
            $response = $connector->send($request);
          
            if ($response->successful()) {
                $data = $response->json();

                // Handle different response structures
                if (isset($data['data'])) {
                    $this->customers = $data['data']['data'] ?? $data['data'];
                    $this->pagination = $data['pagination'] ?? [];
                    $this->totalCustomers = $data['total'] ?? count($this->customers);
                } else {
                    $this->customers = is_array($data) ? $data : [$data];
                    $this->totalCustomers = count($this->customers);
                }
                
                \Log::info('Customers loaded successfully', [
                    'count' => count($this->customers),
                    'business_id' => $this->businessId
                ]);
            } else {
                $this->error = 'Failed to load customers. Status: ' . $response->status();
                \Log::error('Failed to load customers', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            $this->error = 'Error loading customers: ' . $e->getMessage();
            \Log::error('Exception loading customers', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } finally {
            $this->loading = false;
        }
    }

    public function applyFilters()
    {
        // Reset to first page when applying filters
        $this->filters['page'] = 1;
        $this->loadCustomers();
        
        \Log::info('Customer filters applied', [
            'filters' => $this->filters,
            'business_id' => $this->businessId
        ]);
    }

    public function clearFilters()
    {
        $this->filters = [
            'search' => '',
            'limit' => 20,
            'page' => 1,
            'sort_by' => 'created_at',
            'sort_order' => 'desc'
        ];
        $this->loadCustomers();
    }

    public function changePage($page)
    {
        $this->filters['page'] = $page;
        $this->loadCustomers();
    }

    public function sortBy($field)
    {
        // If clicking the same field, toggle sort order
        if ($this->filters['sort_by'] === $field) {
            $this->filters['sort_order'] = $this->filters['sort_order'] === 'asc' ? 'desc' : 'asc';
        } else {
            // New field, default to descending
            $this->filters['sort_by'] = $field;
            $this->filters['sort_order'] = 'desc';
        }
        
        // Reset to first page when sorting
        $this->filters['page'] = 1;
        
        // Apply the sort
        $this->loadCustomers();
    }

    public function getSortIcon($field)
    {
        if ($this->filters['sort_by'] !== $field) {
            return '↕️'; // Neutral sort icon
        }
        
        return $this->filters['sort_order'] === 'asc' ? '↑' : '↓';
    }

    public function getFilteredCustomers()
    {
        if (empty($this->filters['search'])) {
            return $this->customers;
        }

        $searchTerm = strtolower($this->filters['search']);
        
        return collect($this->customers)->filter(function($customer) use ($searchTerm) {
            $fullName = strtolower($customer['full_name'] ?? '');
            $phoneNumber = strtolower($customer['phone_number'] ?? '');
            $alternatePhone = strtolower($customer['Alternate_phone_number'] ?? '');
            $email = strtolower($customer['email'] ?? '');
            $location = strtolower($customer['location'] ?? '');
            
            return str_contains($fullName, $searchTerm) ||
                   str_contains($phoneNumber, $searchTerm) ||
                   str_contains($alternatePhone, $searchTerm) ||
                   str_contains($email, $searchTerm) ||
                   str_contains($location, $searchTerm);
        })->values()->toArray();
    }

    public function viewCustomerDetails($customerId)
    {
        $customer = collect($this->customers)->firstWhere('id', $customerId);
        
        if ($customer) {
            $this->selectedCustomerData = $customer;
            $this->showCustomerModal = true;
        }
    }

    public function closeModals()
    {
        $this->showCustomerModal = false;
        $this->selectedCustomerData = null;
    }

    public function formatDate($date)
    {
        if (empty($date) || $date === 'N/A' || $date === null) {
            return 'N/A';
        }
        
        try {
            return \Carbon\Carbon::parse($date)->format('M d, Y H:i');
        } catch (\Exception $e) {
            \Log::warning('Failed to parse date', [
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            return 'Invalid Date';
        }
    }

    public function getStatusBadgeClass($status)
    {
        return match($status) {
            'completed' => 'bg-green-100 text-green-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
            'in_progress' => 'bg-blue-100 text-blue-800',
            'cancelled' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    public function downloadExcel()
    {
        try {
            $this->downloading = true;
            $this->downloadError = null;

            $connector = new TelconApiConnector();
            $token = session('auth_token');

            if (!$token) {
                $this->downloadError = 'Authentication token not found. Please login again.';
                $this->downloading = false;
                return;
            }

            $request = new DownloadCustomerServiceDetailsExcelRequest($this->businessId, $token);
            $response = $connector->send($request);

            \Log::info('Excel download response', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body_length' => strlen($response->body()),
                'business_id' => $this->businessId
            ]);

            if ($response->successful()) {
                $filename = 'customers_' . date('Y-m-d_H-i-s') . '.xlsx';
                $fileContent = $response->body();
                
                // Validate that the response is actually an Excel file
                if (strpos($fileContent, '<!DOCTYPE html>') !== false || 
                    strpos($fileContent, '<html') !== false ||
                    strpos($fileContent, 'Error - Internal Server Error') !== false) {
                    
                    $this->downloadError = 'API returned an error page instead of Excel file. Please check the API server configuration.';
                    \Log::error('API returned HTML error page instead of Excel file', [
                        'response_preview' => substr($fileContent, 0, 500),
                        'business_id' => $this->businessId
                    ]);
                    return;
                }
                
                // Check if the content looks like an Excel file (XLSX files start with PK)
                if (substr($fileContent, 0, 2) !== 'PK') {
                    // Check if it's CSV data instead (common when Excel export fails)
                    if (strpos($fileContent, ',') !== false && strpos($fileContent, "\n") !== false) {
                        // It's CSV data, save it as CSV instead
                        $csvFilename = 'customers_' . date('Y-m-d_H-i-s') . '.csv';
                        $tempPath = storage_path('app/temp/' . $csvFilename);
                        if (!file_exists(dirname($tempPath))) {
                            mkdir(dirname($tempPath), 0755, true);
                        }
                        file_put_contents($tempPath, $fileContent);
                        
                        // Redirect to download the CSV file
                        return redirect()->route('download.temp', ['filename' => $csvFilename]);
                    } else {
                        $this->downloadError = 'Downloaded file is not a valid Excel file. The API may be returning CSV data instead.';
                        \Log::error('Downloaded content is not a valid Excel file', [
                            'content_start' => substr($fileContent, 0, 100),
                            'business_id' => $this->businessId
                        ]);
                        return;
                    }
                }
                
                // Store file temporarily and redirect to download
                $tempPath = storage_path('app/temp/' . $filename);
                if (!file_exists(dirname($tempPath))) {
                    mkdir(dirname($tempPath), 0755, true);
                }
                file_put_contents($tempPath, $fileContent);
                
                // Redirect to download route
                return redirect()->route('download.temp', ['filename' => $filename]);
            } else {
                // If API fails, try to generate CSV from existing customer data
                \Log::info('Excel API failed, attempting to generate CSV from existing data', [
                    'status' => $response->status(),
                    'customer_count' => count($this->customers),
                    'business_id' => $this->businessId
                ]);
                
                if (count($this->customers) > 0) {
                    $this->generateCsvFromData('customers_' . date('Y-m-d_H-i-s') . '.csv');
                    return;
                } else {
                    $this->downloadError = 'Failed to download Excel file. Status: ' . $response->status() . '. No customer data available for fallback.';
                    \Log::error('Failed to download Excel and no fallback data available', [
                        'status' => $response->status(),
                        'response' => $response->body()
                    ]);
                }
            }
        } catch (\Exception $e) {
            $this->downloadError = 'Error downloading Excel: ' . $e->getMessage();
            \Log::error('Exception downloading Excel', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } finally {
            $this->downloading = false;
        }
    }

    public function downloadPdf()
    {
        try {
            $this->downloading = true;
            $this->downloadError = null;

            $connector = new TelconApiConnector();
            $token = session('auth_token');

            if (!$token) {
                $this->downloadError = 'Authentication token not found. Please login again.';
                $this->downloading = false;
                return;
            }

            $request = new DownloadCustomerServiceDetailsPdfRequest($this->businessId, $token);
            $response = $connector->send($request);

            \Log::info('PDF download response', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body_length' => strlen($response->body()),
                'business_id' => $this->businessId
            ]);

            if ($response->successful()) {
                $filename = 'customers_' . date('Y-m-d_H-i-s') . '.pdf';
                $fileContent = $response->body();
                
                // Validate that the response is actually a PDF file
                if (strpos($fileContent, '<!DOCTYPE html>') !== false || 
                    strpos($fileContent, '<html') !== false ||
                    strpos($fileContent, 'Error - Internal Server Error') !== false) {
                    
                    $this->downloadError = 'API returned an error page instead of PDF file. Please check the API server configuration.';
                    \Log::error('API returned HTML error page instead of PDF file', [
                        'response_preview' => substr($fileContent, 0, 500),
                        'business_id' => $this->businessId
                    ]);
                    return;
                }
                
                // Check if the content looks like a PDF file (PDF files start with %PDF)
                if (substr($fileContent, 0, 4) !== '%PDF') {
                    $this->downloadError = 'Downloaded file is not a valid PDF file. Please try again or contact support.';
                    \Log::error('Downloaded content is not a valid PDF file', [
                        'content_start' => substr($fileContent, 0, 100),
                        'business_id' => $this->businessId
                    ]);
                    return;
                }
                
                // Store file temporarily and redirect to download
                $tempPath = storage_path('app/temp/' . $filename);
                if (!file_exists(dirname($tempPath))) {
                    mkdir(dirname($tempPath), 0755, true);
                }
                file_put_contents($tempPath, $fileContent);
                
                // Redirect to download route
                return redirect()->route('download.temp', ['filename' => $filename]);
            } else {
                // If API fails, try to generate CSV from existing customer data
                \Log::info('PDF API failed, attempting to generate CSV from existing data', [
                    'status' => $response->status(),
                    'customer_count' => count($this->customers),
                    'business_id' => $this->businessId
                ]);
                
                if (count($this->customers) > 0) {
                    $this->generateCsvFromData('customers_' . date('Y-m-d_H-i-s') . '.csv');
                    return;
                } else {
                    $this->downloadError = 'Failed to download PDF file. Status: ' . $response->status() . '. No customer data available for fallback.';
                    \Log::error('Failed to download PDF and no fallback data available', [
                        'status' => $response->status(),
                        'response' => $response->body()
                    ]);
                }
            }
        } catch (\Exception $e) {
            $this->downloadError = 'Error downloading PDF: ' . $e->getMessage();
            \Log::error('Exception downloading PDF', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } finally {
            $this->downloading = false;
        }
    }

    public function downloadCsv()
    {
        try {
            $this->downloading = true;
            $this->downloadError = null;

            $connector = new TelconApiConnector();
            $token = session('auth_token');

            if (!$token) {
                $this->downloadError = 'Authentication token not found. Please login again.';
                $this->downloading = false;
                return;
            }

            $request = new DownloadCustomerServiceDetailsCsvRequest($this->businessId, $token);
            $response = $connector->send($request);

            \Log::info('CSV download response', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body_length' => strlen($response->body()),
                'business_id' => $this->businessId
            ]);

            // If CSV endpoint doesn't exist (404), try Excel endpoint as fallback
            if ($response->status() === 404) {
                \Log::info('CSV endpoint not found, trying Excel endpoint as fallback');
                $excelRequest = new DownloadCustomerServiceDetailsExcelRequest($this->businessId, $token);
                $response = $connector->send($excelRequest);
                
                \Log::info('Excel fallback response', [
                    'status' => $response->status(),
                    'headers' => $response->headers(),
                    'body_length' => strlen($response->body()),
                    'business_id' => $this->businessId
                ]);
            }

            if ($response->successful()) {
                $filename = 'customers_' . date('Y-m-d_H-i-s') . '.csv';
                $fileContent = $response->body();
                
                // Validate that the response is actually a CSV file
                if (strpos($fileContent, '<!DOCTYPE html>') !== false || 
                    strpos($fileContent, '<html') !== false ||
                    strpos($fileContent, 'Error - Internal Server Error') !== false) {
                    
                    $this->downloadError = 'API returned an error page instead of CSV file. Please check the API server configuration.';
                    \Log::error('API returned HTML error page instead of CSV file', [
                        'response_preview' => substr($fileContent, 0, 500),
                        'business_id' => $this->businessId
                    ]);
                    return;
                }
                
                // Store file temporarily and redirect to download
                $tempPath = storage_path('app/temp/' . $filename);
                if (!file_exists(dirname($tempPath))) {
                    mkdir(dirname($tempPath), 0755, true);
                }
                file_put_contents($tempPath, $fileContent);
                
                // Redirect to download route
                return redirect()->route('download.temp', ['filename' => $filename]);
            } else {
                $this->downloadError = 'Failed to download CSV file. Status: ' . $response->status();
                \Log::error('Failed to download CSV', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            $this->downloadError = 'Error downloading CSV: ' . $e->getMessage();
            \Log::error('Exception downloading CSV', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } finally {
            $this->downloading = false;
        }
    }

    private function generateCsvFromData($filename)
    {
        try {
            $customers = $this->getFilteredCustomers();
            
            if (empty($customers)) {
                $this->downloadError = 'No customer data available to generate CSV file.';
                return;
            }

            // Create CSV content
            $csvContent = "ID,Full Name,Phone Number,Alternate Phone,Email,Location,Status,MyMTN App,MoMo App,ADS,RGT,Next of Kin Name,Next of Kin Phone,SIM Serial,Ticket Number,Remarks,Reason for Action,Created At\n";
            
            foreach ($customers as $customer) {
                $csvContent .= sprintf(
                    "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                    $customer['id'] ?? '',
                    $customer['full_name'] ?? '',
                    $customer['phone_number'] ?? '',
                    $customer['Alternate_phone_number'] ?? '',
                    $customer['email'] ?? '',
                    $customer['location'] ?? '',
                    $customer['Status'] ?? '',
                    $customer['MyMTNApp_Activation_Status'] ? 'Active' : 'Inactive',
                    $customer['MomoApp_Activation_Status'] ? 'Active' : 'Inactive',
                    $customer['ADS_Activation_Status'] ? 'Active' : 'Inactive',
                    $customer['RGT_Activation_Status'] ? 'Active' : 'Inactive',
                    $customer['NOK_name'] ?? '',
                    $customer['NOK_phone'] ?? '',
                    $customer['SIM_serial_number'] ?? '',
                    $customer['Ticket_Number'] ?? '',
                    $customer['Remarks'] ?? '',
                    $customer['Reason_for_Action'] ?? '',
                    $this->formatDate($customer['created_at'] ?? null)
                );
            }

            // Store file temporarily and redirect to download
            $tempPath = storage_path('app/temp/' . $filename);
            if (!file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }
            file_put_contents($tempPath, $csvContent);
            
            \Log::info('CSV generated from local data', [
                'filename' => $filename,
                'customer_count' => count($customers),
                'business_id' => $this->businessId
            ]);
            
            // Redirect to download route
            return redirect()->route('download.temp', ['filename' => $filename]);
            
        } catch (\Exception $e) {
            $this->downloadError = 'Error generating CSV from data: ' . $e->getMessage();
            \Log::error('Exception generating CSV from data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}; ?>

<div class="p-4 sm:p-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Customer Service Details</h1>
        <p class="text-sm sm:text-base text-gray-600 mt-1">Manage and view all customer service information</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 sm:p-6 mb-4 sm:mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-3 sm:mb-4">Search & Filters</h3>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <!-- Search -->
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Search Customers</label>
                <input type="text" 
                       wire:model.live.debounce.300ms="filters.search" 
                       placeholder="Search by name, phone, email, or location..." 
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm sm:text-base p-2">
            </div>

            <!-- Sort By -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                <select wire:model.live="filters.sort_by" wire:change="applyFilters" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm sm:text-base">
                    <option value="created_at">Date Created</option>
                    <option value="updated_at">Date Updated</option>
                    <option value="full_name">Name</option>
                    <option value="phone_number">Phone</option>
                    <option value="Status">Status</option>
                </select>
            </div>
            
            <!-- Sort Order -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                <select wire:model.live="filters.sort_order" wire:change="applyFilters" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm sm:text-base">
                    <option value="desc">Descending</option>
                    <option value="asc">Ascending</option>
                </select>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-2 sm:gap-2 mt-3 sm:mt-4">
            <button wire:click="applyFilters" class="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm sm:text-base">
                Apply Filters
            </button>
            <button wire:click="clearFilters" class="w-full sm:w-auto px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 text-sm sm:text-base">
                Clear
            </button>
        </div>
    </div>

    <!-- Download Section -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 sm:p-6 mb-4 sm:mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-3 sm:mb-4">Download Customer Data</h3>
        
        <!-- API Status Notice -->
        <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Download Notice</h3>
                    <div class="mt-1 text-sm text-yellow-700">
                        <p>Excel/PDF downloads may fail due to API server issues. The system will automatically generate a CSV file from the displayed customer data as a fallback.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
            <!-- Excel Download -->
            <button wire:click="downloadExcel" 
                    wire:loading.attr="disabled" 
                    wire:target="downloadExcel"
                    class="flex items-center justify-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed text-sm sm:text-base">
                <svg wire:loading.remove wire:target="downloadExcel" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <svg wire:loading wire:target="downloadExcel" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span wire:loading.remove wire:target="downloadExcel">📊 Download Excel</span>
                <span wire:loading wire:target="downloadExcel">Downloading...</span>
            </button>

            <!-- CSV Download -->
            <button wire:click="downloadCsv" 
                    wire:loading.attr="disabled" 
                    wire:target="downloadCsv"
                    class="flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed text-sm sm:text-base">
                <svg wire:loading.remove wire:target="downloadCsv" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <svg wire:loading wire:target="downloadCsv" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span wire:loading.remove wire:target="downloadCsv">📋 Download CSV</span>
                <span wire:loading wire:target="downloadCsv">Downloading...</span>
            </button>

            <!-- PDF Download -->
            <button wire:click="downloadPdf" 
                    wire:loading.attr="disabled" 
                    wire:target="downloadPdf"
                    class="flex items-center justify-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed text-sm sm:text-base">
                <svg wire:loading.remove wire:target="downloadPdf" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <svg wire:loading wire:target="downloadPdf" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span wire:loading.remove wire:target="downloadPdf">📄 Download PDF</span>
                <span wire:loading wire:target="downloadPdf">Downloading...</span>
            </button>
        </div>

        <!-- Download Error -->
        @if($downloadError)
            <div class="mt-3 bg-red-50 border border-red-200 rounded-md p-3">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Download Error</h3>
                        <div class="mt-1 text-sm text-red-700">{{ $downloadError }}</div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Loading State -->
    @if($loading)
        <div class="flex justify-center items-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span class="ml-2 text-gray-600">Loading customers...</span>
        </div>
    @endif

    <!-- Error State -->
    @if($error)
        <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Error</h3>
                    <div class="mt-2 text-sm text-red-700">{{ $error }}</div>
                </div>
            </div>
        </div>
    @endif

    <!-- Customers Display -->
    @if(!$loading && !$error && count($this->getFilteredCustomers()) > 0)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-3 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">
                    Customers ({{ count($this->getFilteredCustomers()) }})
                </h3>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden lg:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">S/N</th>
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <button wire:click="sortBy('full_name')" class="flex items-center space-x-1 hover:text-gray-700 focus:outline-none">
                                    <span>Customer</span>
                                    <span class="text-xs">{{ $this->getSortIcon('full_name') }}</span>
                                </button>
                            </th>
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <button wire:click="sortBy('Status')" class="flex items-center space-x-1 hover:text-gray-700 focus:outline-none">
                                    <span>Status</span>
                                    <span class="text-xs">{{ $this->getSortIcon('Status') }}</span>
                                </button>
                            </th>
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activation Status</th>
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <button wire:click="sortBy('created_at')" class="flex items-center space-x-1 hover:text-gray-700 focus:outline-none">
                                    <span>Date</span>
                                    <span class="text-xs">{{ $this->getSortIcon('created_at') }}</span>
                                </button>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($this->getFilteredCustomers() as $customer)
                            <tr class="hover:bg-gray-50">
                                <!-- Serial Number -->
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $loop->iteration }}
                                </td>
                                
                                <!-- Customer Info -->
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $customer['full_name'] ?? 'N/A' }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        ID: {{ $customer['id'] ?? 'N/A' }}
                                    </div>
                                </td>

                                <!-- Contact Info -->
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        📞 {{ $customer['phone_number'] ?? 'N/A' }}
                                    </div>
                                    @if($customer['Alternate_phone_number'])
                                        <div class="text-sm text-gray-500">
                                            📱 {{ $customer['Alternate_phone_number'] }}
                                        </div>
                                    @endif
                                    @if($customer['email'])
                                        <div class="text-sm text-gray-500">
                                            ✉️ {{ $customer['email'] }}
                                        </div>
                                    @endif
                                </td>

                                <!-- Location -->
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        📍 {{ $customer['location'] ?? 'N/A' }}
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $this->getStatusBadgeClass($customer['Status'] ?? 'unknown') }}">
                                        {{ ucfirst($customer['Status'] ?? 'Unknown') }}
                                    </span>
                                </td>

                                <!-- Activation Status -->
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-wrap gap-1">
                                        <span class="inline-flex px-1 py-0.5 text-xs font-semibold rounded {{ $customer['MyMTNApp_Activation_Status'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            MyMTN
                                        </span>
                                        <span class="inline-flex px-1 py-0.5 text-xs font-semibold rounded {{ $customer['MomoApp_Activation_Status'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            MoMo
                                        </span>
                                        <span class="inline-flex px-1 py-0.5 text-xs font-semibold rounded {{ $customer['ADS_Activation_Status'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            ADS
                                        </span>
                                        <span class="inline-flex px-1 py-0.5 text-xs font-semibold rounded {{ $customer['RGT_Activation_Status'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            RGT
                                        </span>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 sm:px-6 py-4 text-sm font-medium">
                                    <button wire:click="viewCustomerDetails({{ $customer['id'] }})" 
                                            class="text-indigo-600 hover:text-indigo-900 text-xs bg-indigo-50 hover:bg-indigo-100 px-2 py-1 rounded">
                                        👤 View Details
                                    </button>
                                </td>

                                <!-- Date -->
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $this->formatDate($customer['created_at'] ?? null) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View -->
            <div class="lg:hidden">
                @foreach($this->getFilteredCustomers() as $customer)
                    <div class="border-b border-gray-200 p-4 hover:bg-gray-50">
                        <!-- Header with Customer Name and Status -->
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center space-x-2">
                                <span class="text-xs text-gray-500">#{{ $loop->iteration }}</span>
                                <span class="text-sm font-medium text-gray-900">{{ $customer['full_name'] ?? 'N/A' }}</span>
                            </div>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $this->getStatusBadgeClass($customer['Status'] ?? 'unknown') }}">
                                {{ ucfirst($customer['Status'] ?? 'Unknown') }}
                            </span>
                        </div>

                        <!-- Contact Info -->
                        <div class="mb-3">
                            <div class="text-sm text-gray-900">
                                📞 {{ $customer['phone_number'] ?? 'N/A' }}
                            </div>
                            @if($customer['Alternate_phone_number'])
                                <div class="text-xs text-gray-500">
                                    📱 {{ $customer['Alternate_phone_number'] }}
                                </div>
                            @endif
                            @if($customer['email'])
                                <div class="text-xs text-gray-500">
                                    ✉️ {{ $customer['email'] }}
                                </div>
                            @endif
                        </div>

                        <!-- Location -->
                        @if($customer['location'])
                            <div class="mb-3">
                                <div class="text-xs text-gray-500">
                                    📍 {{ $customer['location'] }}
                                </div>
                            </div>
                        @endif

                        <!-- Activation Status -->
                        <div class="mb-3">
                            <div class="text-xs text-gray-600 mb-1">Activation Status:</div>
                            <div class="flex flex-wrap gap-1">
                                <span class="inline-flex px-1 py-0.5 text-xs font-semibold rounded {{ $customer['MyMTNApp_Activation_Status'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    MyMTN
                                </span>
                                <span class="inline-flex px-1 py-0.5 text-xs font-semibold rounded {{ $customer['MomoApp_Activation_Status'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    MoMo
                                </span>
                                <span class="inline-flex px-1 py-0.5 text-xs font-semibold rounded {{ $customer['ADS_Activation_Status'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    ADS
                                </span>
                                <span class="inline-flex px-1 py-0.5 text-xs font-semibold rounded {{ $customer['RGT_Activation_Status'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    RGT
                                </span>
                            </div>
                        </div>

                        <!-- Date and Action -->
                        <div class="flex items-center justify-between">
                            <div class="text-xs text-gray-500">
                                {{ $this->formatDate($customer['created_at'] ?? null) }}
                            </div>
                            <button wire:click="viewCustomerDetails({{ $customer['id'] }})" 
                                    class="text-xs bg-indigo-50 text-indigo-600 hover:bg-indigo-100 px-3 py-1 rounded-md font-medium">
                                👤 View Details
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Empty State -->
    @if(!$loading && !$error && count($this->getFilteredCustomers()) === 0)
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No customers found</h3>
            <p class="mt-1 text-sm text-gray-500">Try adjusting your search criteria or check back later.</p>
        </div>
    @endif

    <!-- Customer Details Modal -->
    @if($showCustomerModal && $selectedCustomerData)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 backdrop-blur-sm overflow-y-auto h-full w-full z-50" wire:click="closeModals">
            <div class="relative top-4 sm:top-20 mx-auto p-3 sm:p-5 border w-11/12 sm:w-10/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white" wire:click.stop>
                <div class="mt-1 sm:mt-3">
                    <div class="flex items-center justify-between mb-3 sm:mb-4">
                        <h3 class="text-base sm:text-lg font-medium text-gray-900">Customer Details</h3>
                        <button wire:click="closeModals" class="text-gray-400 hover:text-gray-600 p-1">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700">Full Name</label>
                            <p class="mt-1 text-xs sm:text-sm text-gray-900">{{ $selectedCustomerData['full_name'] ?? 'N/A' }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700">Phone Number</label>
                            <p class="mt-1 text-xs sm:text-sm text-gray-900">{{ $selectedCustomerData['phone_number'] ?? 'N/A' }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700">Email</label>
                            <p class="mt-1 text-xs sm:text-sm text-gray-900 break-all">{{ $selectedCustomerData['email'] ?? 'N/A' }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700">Location</label>
                            <p class="mt-1 text-xs sm:text-sm text-gray-900">{{ $selectedCustomerData['location'] ?? 'N/A' }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700">Next of Kin Name</label>
                            <p class="mt-1 text-xs sm:text-sm text-gray-900">{{ $selectedCustomerData['NOK_name'] ?? 'N/A' }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700">Next of Kin Phone</label>
                            <p class="mt-1 text-xs sm:text-sm text-gray-900">{{ $selectedCustomerData['NOK_phone'] ?? 'N/A' }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700">Alternate Phone</label>
                            <p class="mt-1 text-xs sm:text-sm text-gray-900">{{ $selectedCustomerData['Alternate_phone_number'] ?? 'N/A' }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700">SIM Serial Number</label>
                            <p class="mt-1 text-xs sm:text-sm text-gray-900 break-all">{{ $selectedCustomerData['SIM_serial_number'] ?? 'N/A' }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700">Status</label>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $this->getStatusBadgeClass($selectedCustomerData['Status'] ?? 'unknown') }}">
                                {{ ucfirst($selectedCustomerData['Status'] ?? 'Unknown') }}
                            </span>
                        </div>

<div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700">Ticket Number</label>
                            <p class="mt-1 text-xs sm:text-sm text-gray-900">{{ $selectedCustomerData['Ticket_Number'] ?? 'N/A' }}</p>
                        </div>
                    </div>
                    
                    <!-- Activation Statuses -->
                    <div class="mt-4 sm:mt-6">
                        <h4 class="text-sm sm:text-md font-medium text-gray-900 mb-3">Activation Statuses</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                            <div class="text-center">
                                <div class="text-xs sm:text-sm font-medium text-gray-700">MyMTN App</div>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $selectedCustomerData['MyMTNApp_Activation_Status'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $selectedCustomerData['MyMTNApp_Activation_Status'] ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <div class="text-center">
                                <div class="text-xs sm:text-sm font-medium text-gray-700">MoMo App</div>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $selectedCustomerData['MomoApp_Activation_Status'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $selectedCustomerData['MomoApp_Activation_Status'] ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <div class="text-center">
                                <div class="text-xs sm:text-sm font-medium text-gray-700">ADS</div>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $selectedCustomerData['ADS_Activation_Status'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $selectedCustomerData['ADS_Activation_Status'] ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <div class="text-center">
                                <div class="text-xs sm:text-sm font-medium text-gray-700">RGT</div>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $selectedCustomerData['RGT_Activation_Status'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $selectedCustomerData['RGT_Activation_Status'] ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    @if($selectedCustomerData['Remarks'])
                        <div class="mt-3 sm:mt-4">
                            <label class="block text-xs sm:text-sm font-medium text-gray-700">Remarks</label>
                            <p class="mt-1 text-xs sm:text-sm text-gray-900">{{ $selectedCustomerData['Remarks'] }}</p>
                        </div>
                    @endif
                    
                    @if($selectedCustomerData['Reason_for_Action'])
                        <div class="mt-3 sm:mt-4">
                            <label class="block text-xs sm:text-sm font-medium text-gray-700">Reason for Action</label>
                            <p class="mt-1 text-xs sm:text-sm text-gray-900">{{ $selectedCustomerData['Reason_for_Action'] }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
