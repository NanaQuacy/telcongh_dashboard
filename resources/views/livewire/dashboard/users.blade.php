<?php

use Livewire\Volt\Component;
use App\Services\UserManagementService;
use App\Http\Integrations\TelconApiConnector;
use Illuminate\Support\Facades\Log;

new class extends Component {
    public $loading = false;
    public $error = null;
    
    // Users data
    public $users = [];
    public $usersTotal = 0;
    public $usersPerPage = 15;
    public $usersCurrentPage = 1;
    public $usersLastPage = 1;
    public $usersFrom = 0;
    public $usersTo = 0;
    
    // Business data
    public $businessId = null;
    public $businessName = '';
    public $businessCode = '';
    
    // Search and filters
    public $searchTerm = '';
    public $statusFilter = 'all'; // all, active, inactive, verified, unverified
    
    // Role assignment
    public $showRoleModal = false;
    public $selectedUserForRole = null;
    public $selectedUserData = null;
    public $availableRoles = [];
    public $selectedRoleId = null;
    public $roleAssignmentLoading = false;
    
    protected $userManagementService;
    protected $rolePermissionService;

    public function mount()
    {
       
        $this->initializeService();
        $this->loadBusinessInfo();
        $this->loadUsers();
    }

    private function initializeService()
    {
        if (!$this->userManagementService) {
            $this->userManagementService = new UserManagementService(new TelconApiConnector());
        }
        if (!$this->rolePermissionService) {
            $this->rolePermissionService = new \App\Services\RolePermissionService(new TelconApiConnector());
        }
    }

    private function loadBusinessInfo()
    {
        // Get business info from session or request
        $selectedBusiness = session('selected_business');
        $this->businessId = $selectedBusiness['id'] ?? 4; // Default to 4 if not set
        $this->businessName = $selectedBusiness['name'] ?? 'Current Business';
        $this->businessCode = $selectedBusiness['business_code'] ?? 'BUS001';
    }

    public function refreshData()
    {
        $this->loadUsers();
    }

    public function loadUsers($page = 1)
    {
        try {
            $this->initializeService();
            $this->loading = true;
            $this->error = null;
            
            $token = session('auth_token');
            $usersResponse = $this->userManagementService->getBusinessUsers(
                $this->businessId, 
                $token, 
                $page, 
                $this->usersPerPage
            );
            
            $this->users = [];
            foreach ($usersResponse->getUsers() as $user) {
                $this->users[] = [
                    'id' => $user->getId(),
                    'user_id' => $user->getUserId(),
                    'business_id' => $user->getBusinessId(),
                    'is_active' => $user->isActive(),
                    'is_verified' => $user->isVerified(),
                    'is_deleted' => $user->isDeleted(),
                    'created_at' => $user->getCreatedAt(),
                    'updated_at' => $user->getUpdatedAt(),
                    'user' => $user->getUser(),
                    'business' => $user->getBusiness(),
                    'roles' => $user->getRoles() ?? [],
                ];
            }
            
            // Update pagination data
            $this->usersTotal = $usersResponse->getTotal();
            $this->usersPerPage = $usersResponse->getPerPage();
            $this->usersCurrentPage = $usersResponse->getCurrentPage();
            $this->usersLastPage = $usersResponse->getLastPage();
            $this->usersFrom = $usersResponse->getFrom();
            $this->usersTo = $usersResponse->getTo();
            
            Log::info('Business users loaded successfully', [
                'business_id' => $this->businessId,
                'count' => count($this->users),
                'total' => $this->usersTotal,
                'current_page' => $this->usersCurrentPage,
                'last_page' => $this->usersLastPage
            ]);
        } catch (\Exception $e) {
            $this->error = 'Failed to load users: ' . $e->getMessage();
            Log::error('Error loading business users: ' . $e->getMessage());
            $this->users = [];
        } finally {
            $this->loading = false;
        }
    }

    // Pagination methods
    public function goToUsersPage($page)
    {
        if ($page >= 1 && $page <= $this->usersLastPage) {
            $this->usersCurrentPage = $page;
            $this->loadUsers($page);
        }
    }

    public function nextUsersPage()
    {
        if ($this->usersCurrentPage < $this->usersLastPage) {
            $this->goToUsersPage($this->usersCurrentPage + 1);
        }
    }

    public function previousUsersPage()
    {
        if ($this->usersCurrentPage > 1) {
            $this->goToUsersPage($this->usersCurrentPage - 1);
        }
    }

    // Filter methods
    public function updatedSearchTerm()
    {
        // Implement search functionality if needed
        $this->loadUsers(1);
    }

    public function updatedStatusFilter()
    {
        // Implement status filtering if needed
        $this->loadUsers(1);
    }

    public function getFilteredUsers()
    {
        $filtered = collect($this->users);

        // Apply search filter
        if (!empty($this->searchTerm)) {
            $filtered = $filtered->filter(function ($user) {
                $searchLower = strtolower($this->searchTerm);
                return str_contains(strtolower($user['user']['name'] ?? ''), $searchLower) ||
                       str_contains(strtolower($user['user']['email'] ?? ''), $searchLower) ||
                       str_contains(strtolower($user['user']['phone'] ?? ''), $searchLower);
            });
        }

        // Apply status filter
        if ($this->statusFilter !== 'all') {
            $filtered = $filtered->filter(function ($user) {
                switch ($this->statusFilter) {
                    case 'active':
                        return $user['is_active'];
                    case 'inactive':
                        return !$user['is_active'];
                    case 'verified':
                        return $user['is_verified'];
                    case 'unverified':
                        return !$user['is_verified'];
                    default:
                        return true;
                }
            });
        }

        return $filtered->values()->toArray();
    }

    public function formatDate($date)
    {
        if (empty($date)) return 'N/A';
        try {
            return \Carbon\Carbon::parse($date)->format('M d, Y H:i');
        } catch (\Exception $e) {
            return $date;
        }
    }

    // Role Assignment Methods
    public function openRoleModal($userId)
    {
        try {
            $this->initializeService();
            $this->roleAssignmentLoading = true;
            $this->error = null;
            
            // Find the user data
            $userData = collect($this->users)->firstWhere('user_id', $userId);
            if (!$userData) {
                $this->error = 'User not found.';
                return;
            }
            
            $this->selectedUserForRole = $userId;
            $this->selectedUserData = $userData;
            
            // Load available roles
            $token = session('auth_token');
            $rolesResponse = $this->rolePermissionService->getAllRoles($token);
            
            $this->availableRoles = [];
            foreach ($rolesResponse->getRoles() as $role) {
                $this->availableRoles[] = [
                    'id' => $role->getId(),
                    'name' => $role->getName(),
                    'guard_name' => $role->getGuardName(),
                ];
            }
            
            // Set current role if user has one
            $currentRoles = $userData['roles'] ?? [];
            if (!empty($currentRoles) && is_array($currentRoles)) {
                $this->selectedRoleId = $currentRoles[0]['id'] ?? null;
            } else {
                $this->selectedRoleId = null;
            }
            
            $this->showRoleModal = true;
            
            Log::info('Role modal opened', [
                'user_id' => $userId,
                'available_roles_count' => count($this->availableRoles),
                'current_role_id' => $this->selectedRoleId
            ]);
            
        } catch (\Exception $e) {
            $this->error = 'Failed to load roles: ' . $e->getMessage();
            Log::error('Error opening role modal: ' . $e->getMessage());
        } finally {
            $this->roleAssignmentLoading = false;
        }
    }

    public function closeRoleModal()
    {
        $this->showRoleModal = false;
        $this->selectedUserForRole = null;
        $this->selectedUserData = null;
        $this->availableRoles = [];
        $this->selectedRoleId = null;
        $this->roleAssignmentLoading = false;
    }

    public function assignRole()
    {
        if (!$this->selectedUserForRole || !$this->selectedRoleId) {
            $this->error = 'Please select a role.';
            return;
        }

        try {
            $this->initializeService();
            $this->roleAssignmentLoading = true;
            $this->error = null;
            
            $token = session('auth_token');
            
            // First, remove existing roles if any
            $currentRoles = $this->selectedUserData['roles'] ?? [];
            if (!empty($currentRoles) && is_array($currentRoles)) {
                foreach ($currentRoles as $role) {
                    try {
                        $this->rolePermissionService->removeRoleFromUser(
                            $role['id'],
                            $this->selectedUserForRole,
                            $token
                        );
                    } catch (\Exception $e) {
                        Log::warning('Failed to remove existing role', [
                            'role_id' => $role['id'],
                            'user_id' => $this->selectedUserForRole,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
            // Assign new role
            $success = $this->rolePermissionService->assignRoleToUser(
                $this->selectedRoleId,
                $this->selectedUserForRole,
                $token
            );
            
            if ($success) {
                // Update local user data
                $userIndex = collect($this->users)->search(function ($user) {
                    return $user['user_id'] == $this->selectedUserForRole;
                });
                
                if ($userIndex !== false) {
                    $selectedRole = collect($this->availableRoles)->firstWhere('id', $this->selectedRoleId);
                    if ($selectedRole) {
                        $this->users[$userIndex]['roles'] = [$selectedRole];
                    }
                }
                
                $this->closeRoleModal();
                session()->flash('message', 'User role updated successfully!');
                
                Log::info('Role assigned successfully', [
                    'user_id' => $this->selectedUserForRole,
                    'role_id' => $this->selectedRoleId
                ]);
            } else {
                $this->error = 'Failed to assign role. Please try again.';
            }
            
        } catch (\Exception $e) {
            $this->error = 'Failed to assign role: ' . $e->getMessage();
            Log::error('Error assigning role: ' . $e->getMessage());
        } finally {
            $this->roleAssignmentLoading = false;
        }
    }
}; ?>

<div class="py-4 sm:py-6 min-h-screen">
    <div class="max-w-7xl mx-auto px-2 sm:px-4 lg:px-8">
        <!-- Flash Messages -->
        @if (session()->has('message'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ session('message') }}
            </div>
        @endif

        <!-- Error Messages -->
        @if($error)
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                {{ $error }}
            </div>
        @endif


        <!-- Loading Indicator -->
        @if($loading)
            <div class="mb-4 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded">
                <div class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Loading users...
                </div>
            </div>
        @endif

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6 space-y-4 sm:space-y-0">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Business Users Management</h1>
                <p class="text-xs sm:text-sm text-gray-600 mt-1">
                    Managing users for <span class="font-medium">{{ $businessName }}</span> 
                    <span class="text-gray-500">({{ $businessCode }})</span>
                </p>
            </div>
            <button wire:click="refreshData" 
                    @if($loading) disabled @endif
                    class="inline-flex items-center justify-center px-3 py-2 sm:px-4 border border-gray-300 rounded-md shadow-sm text-xs sm:text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg class="h-3 w-3 sm:h-4 sm:w-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <span class="hidden sm:inline">Refresh</span>
                <span class="sm:hidden">↻</span>
            </button>
        </div>

        <!-- Search and Filters -->
        <div class="bg-white shadow rounded-lg p-4 sm:p-6 mb-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Search Users</label>
                    <input type="text" 
                           wire:model.live.debounce.300ms="searchTerm"
                           placeholder="Search by name, email, or phone..."
                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                <div>
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Status Filter</label>
                    <select wire:model.live="statusFilter" 
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="all">All Users</option>
                        <option value="active">Active Only</option>
                        <option value="inactive">Inactive Only</option>
                        <option value="verified">Verified Only</option>
                        <option value="unverified">Unverified Only</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <div class="text-xs sm:text-sm text-gray-500">
                        Showing {{ count($this->getFilteredUsers()) }} of {{ $usersTotal }} users
                    </div>
                </div>
            </div>
        </div>

        <!-- Users List -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-3 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-2 sm:space-y-0">
                    <h3 class="text-base sm:text-lg font-medium text-gray-900">Business Users</h3>
                    <div class="text-xs sm:text-sm text-gray-500">
                        Page {{ $usersCurrentPage }} of {{ $usersLastPage }}
                    </div>
                </div>
            </div>
            
            <!-- Mobile Card View -->
            <div class="block sm:hidden">
                <div class="divide-y divide-gray-200 max-h-96 overflow-y-auto">
                    @foreach($this->getFilteredUsers() as $user)
                        <div class="p-4">
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0 h-8 w-8">
                                    <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                                        <span class="text-xs font-medium text-blue-600">
                                            {{ strtoupper(substr($user['user']['name'] ?? 'U', 0, 2)) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 truncate">
                                                {{ $user['user']['name'] ?? 'Unknown User' }}
                                            </p>
                                            <p class="text-xs text-gray-500">ID: {{ $user['user_id'] }}</p>
                                        </div>
                                        <div class="flex space-x-1">
                                            <button wire:click="openRoleModal({{ $user['user_id'] }})" 
                                                    class="text-purple-600 hover:text-purple-900" 
                                                    title="Assign/Change Role">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                                </svg>
                                            </button>
                                            <button class="text-blue-600 hover:text-blue-900" title="View Details">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                            </button>
                                            <button class="text-green-600 hover:text-green-900" title="Edit User">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </button>
                                            <button class="text-red-600 hover:text-red-900" title="Delete User">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-2 space-y-1">
                                        <p class="text-xs text-gray-600">{{ $user['user']['email'] ?? 'N/A' }}</p>
                                        <p class="text-xs text-gray-600">{{ $user['user']['phone'] ?? 'N/A' }}</p>
                                        <div class="flex flex-wrap gap-1 mt-2">
                                            @if($user['is_active'])
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Active
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    Inactive
                                                </span>
                                            @endif
                                            
                                            @if($user['is_verified'])
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    Verified
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    Unverified
                                                </span>
                                            @endif
                                        </div>
                                        @if(!empty($user['roles']) && is_array($user['roles']))
                                            <div class="flex flex-wrap gap-1 mt-1">
                                                @foreach($user['roles'] as $role)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                        {{ $role['name'] ?? 'Unknown Role' }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-500">No roles assigned</span>
                                        @endif
                                        <p class="text-xs text-gray-500 mt-1">{{ $this->formatDate($user['created_at']) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            
            <!-- Desktop Table View -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-3 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                            <th class="px-3 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-3 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Roles</th>
                            <th class="px-3 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                            <th class="px-3 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($this->getFilteredUsers() as $user)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 lg:px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 lg:h-10 lg:w-10">
                                            <div class="h-8 w-8 lg:h-10 lg:w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                <span class="text-xs lg:text-sm font-medium text-blue-600">
                                                    {{ strtoupper(substr($user['user']['name'] ?? 'U', 0, 2)) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-3 lg:ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $user['user']['name'] ?? 'Unknown User' }}
                                            </div>
                                            <div class="text-xs lg:text-sm text-gray-500">
                                                ID: {{ $user['user_id'] }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 lg:px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $user['user']['email'] ?? 'N/A' }}</div>
                                    <div class="text-xs lg:text-sm text-gray-500">{{ $user['user']['phone'] ?? 'N/A' }}</div>
                                </td>
                                <td class="px-3 lg:px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col space-y-1">
                                        @if($user['is_active'])
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                Inactive
                                            </span>
                                        @endif
                                        
                                        @if($user['is_verified'])
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                Verified
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                Unverified
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 lg:px-6 py-4 whitespace-nowrap">
                                    @if(!empty($user['roles']) && is_array($user['roles']))
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($user['roles'] as $role)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                    {{ $role['name'] ?? 'Unknown Role' }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-xs lg:text-sm text-gray-500">No roles assigned</span>
                                    @endif
                                </td>
                                <td class="px-3 lg:px-6 py-4 whitespace-nowrap text-xs lg:text-sm text-gray-500">
                                    {{ $this->formatDate($user['created_at']) }}
                                </td>
                                        <td class="px-3 lg:px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-1 lg:space-x-2">
                                                <button wire:click="openRoleModal({{ $user['user_id'] }})" 
                                                        class="text-purple-600 hover:text-purple-900" 
                                                        title="Assign/Change Role">
                                                    <svg class="h-3 w-3 lg:h-4 lg:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                                    </svg>
                                                </button>
                                                <button class="text-blue-600 hover:text-blue-900" title="View Details">
                                                    <svg class="h-3 w-3 lg:h-4 lg:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                </button>
                                                <button class="text-green-600 hover:text-green-900" title="Edit User">
                                                    <svg class="h-3 w-3 lg:h-4 lg:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </button>
                                                <button class="text-red-600 hover:text-red-900" title="Delete User">
                                                    <svg class="h-3 w-3 lg:h-4 lg:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            @if($usersLastPage > 1)
            <div class="bg-white px-2 sm:px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                <!-- Mobile Pagination -->
                <div class="flex-1 flex justify-between sm:hidden">
                    <button wire:click="previousUsersPage" 
                            @if($usersCurrentPage <= 1) disabled @endif
                            class="relative inline-flex items-center px-3 py-2 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Prev
                    </button>
                    <div class="flex items-center space-x-1">
                        <span class="text-xs text-gray-500">Page</span>
                        <span class="text-xs font-medium text-gray-900">{{ $usersCurrentPage }}</span>
                        <span class="text-xs text-gray-500">of</span>
                        <span class="text-xs font-medium text-gray-900">{{ $usersLastPage }}</span>
                    </div>
                    <button wire:click="nextUsersPage" 
                            @if($usersCurrentPage >= $usersLastPage) disabled @endif
                            class="relative inline-flex items-center px-3 py-2 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        Next
                        <svg class="h-3 w-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>
                
                <!-- Desktop Pagination -->
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing
                            <span class="font-medium">{{ $usersFrom }}</span>
                            to
                            <span class="font-medium">{{ $usersTo }}</span>
                            of
                            <span class="font-medium">{{ $usersTotal }}</span>
                            results
                        </p>
                    </div>
<div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <!-- Previous Button -->
                            <button wire:click="previousUsersPage" 
                                    @if($usersCurrentPage <= 1) disabled @endif
                                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span class="sr-only">Previous</span>
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            
                            <!-- Page Numbers -->
                            @php
                                $start = max(1, $usersCurrentPage - 2);
                                $end = min($usersLastPage, $usersCurrentPage + 2);
                            @endphp
                            
                            @if($start > 1)
                                <button wire:click="goToUsersPage(1)" 
                                        class="relative inline-flex items-center px-3 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    1
                                </button>
                                @if($start > 2)
                                    <span class="relative inline-flex items-center px-3 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                        ...
                                    </span>
                                @endif
                            @endif
                            
                            @for($i = $start; $i <= $end; $i++)
                                <button wire:click="goToUsersPage({{ $i }})" 
                                        class="relative inline-flex items-center px-3 py-2 border text-sm font-medium {{ $i === $usersCurrentPage ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                                    {{ $i }}
                                </button>
                            @endfor
                            
                            @if($end < $usersLastPage)
                                @if($end < $usersLastPage - 1)
                                    <span class="relative inline-flex items-center px-3 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                        ...
                                    </span>
                                @endif
                                <button wire:click="goToUsersPage({{ $usersLastPage }})" 
                                        class="relative inline-flex items-center px-3 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    {{ $usersLastPage }}
                                </button>
                            @endif
                            
                            <!-- Next Button -->
                            <button wire:click="nextUsersPage" 
                                    @if($usersCurrentPage >= $usersLastPage) disabled @endif
                                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span class="sr-only">Next</span>
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </nav>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Role Assignment Modal -->
    @if($showRoleModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay with blur -->
            <div class="fixed inset-0 bg-opacity-75 backdrop-blur-sm transition-opacity z-40" aria-hidden="true"></div>

            <!-- Modal panel -->
            <div class="relative z-50 inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-purple-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Assign Role to User
                            </h3>
                            <div class="mt-2">
                                @if($selectedUserData)
                                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                <span class="text-sm font-medium text-blue-600">
                                                    {{ strtoupper(substr($selectedUserData['user']['name'] ?? 'U', 0, 2)) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">
                                                {{ $selectedUserData['user']['name'] ?? 'Unknown User' }}
                                            </p>
                                            <p class="text-sm text-gray-500 truncate">
                                                {{ $selectedUserData['user']['email'] ?? 'N/A' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <div class="space-y-4">
                                    <div>
                                        <label for="role-select" class="block text-sm font-medium text-gray-700 mb-2">
                                            Select Role
                                        </label>
                                        @if($roleAssignmentLoading)
                                            <div class="flex items-center justify-center py-4">
                                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-purple-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <span class="text-sm text-gray-500">Loading roles...</span>
                                            </div>
                                        @else
                                            <select wire:model="selectedRoleId" 
                                                    id="role-select"
                                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 text-sm">
                                                <option value="">Select a role...</option>
                                                @foreach($availableRoles as $role)
                                                    <option value="{{ $role['id'] }}">{{ $role['name'] }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </div>

                                    @if(!empty($selectedUserData['roles']) && is_array($selectedUserData['roles']))
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Current Role
                                        </label>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($selectedUserData['roles'] as $role)
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                                                    {{ $role['name'] ?? 'Unknown Role' }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button wire:click="assignRole" 
                            @if($roleAssignmentLoading || !$selectedRoleId) disabled @endif
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        @if($roleAssignmentLoading)
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Assigning...
                        @else
                            Assign Role
                        @endif
                    </button>
                    <button wire:click="closeRoleModal" 
                            @if($roleAssignmentLoading) disabled @endif
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
