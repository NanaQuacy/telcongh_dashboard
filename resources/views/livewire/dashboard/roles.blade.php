<?php

use Livewire\Volt\Component;
use App\Services\RolePermissionService;
use App\Http\Integrations\TelconApiConnector;
use Illuminate\Support\Facades\Log;

new class extends Component {
    public $activeTab = 'roles';
    public $loading = false;
    public $error = null;
    
    // Roles data
    public $roles = [];
    public $newRoleName = '';
    public $newRoleDescription = '';
    public $editingRole = null;
    public $editRoleName = '';
    public $editRoleDescription = '';
    
    // Permissions data
    public $permissions = [];
    public $newPermissionName = '';
    public $newPermissionDescription = '';
    public $editingPermission = null;
    public $editPermissionName = '';
    public $editPermissionDescription = '';
    
    // Role-Permission assignments
    public $rolePermissions = [];
    public $selectedRole = null;
    public $selectedPermissions = [];

    protected $rolePermissionService;

    public function mount()
    {
        $this->initializeService();
        $this->loadRoles();
        $this->loadPermissions();
        $this->loadRolePermissions();
    }

    private function initializeService()
    {
        if (!$this->rolePermissionService) {
            $this->rolePermissionService = new RolePermissionService(new TelconApiConnector());
        }
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function refreshData()
    {
        $this->loadRoles();
        $this->loadPermissions();
        $this->loadRolePermissions();
    }

    // Roles Management
    public function loadRoles()
    {
        try {
            $this->initializeService();
            $this->loading = true;
            $this->error = null;
            
            $token = session('auth_token');
            $rolesResponse = $this->rolePermissionService->getAllRoles($token);
            
            // Debug: Log the raw response
            Log::info('Raw roles response', ['response' => $rolesResponse]);
            
            $this->roles = [];

            foreach ($rolesResponse->getRoles() as $role) {
                $this->roles[] = [
                    'id' => $role->getId(),
                    'name' => $role->getName(),
                    'guard_name' => $role->getGuardName(),
                    'created_at' => $role->getCreatedAt(),
                    'permissions' => $role->getPermissions() ?? [],
                    'permissions_count' => $role->getPermissionsCount() ?? 0,
                ];
            }
            
            Log::info('Roles loaded successfully', ['count' => count($this->roles)]);
        } catch (\Exception $e) {
            $this->error = 'Failed to load roles: ' . $e->getMessage();
            Log::error('Error loading roles: ' . $e->getMessage());
            $this->roles = [];
        } finally {
            $this->loading = false;
        }
    }

    public function createRole()
    {
        $this->validate([
            'newRoleName' => 'required|string|max:255',
            'newRoleDescription' => 'required|string|max:500',
        ]);

        try {
            $this->initializeService();
            $this->loading = true;
            $this->error = null;
            
            $token = session('auth_token');
            $newRole = $this->rolePermissionService->createRole(
                $this->newRoleName,
                $this->newRoleDescription,
                $token
            );
            
            // Add the new role to the local array
            $this->roles[] = [
                'id' => $newRole->getId(),
                'name' => $newRole->getName(),
                'guard_name' => $newRole->getGuardName(),
                'created_at' => $newRole->getCreatedAt(),
                'permissions' => $newRole->getPermissions() ?? [],
                'permissions_count' => $newRole->getPermissionsCount() ?? 0,
            ];
            
            $this->newRoleName = '';
            $this->newRoleDescription = '';
            
            session()->flash('message', 'Role created successfully!');
            Log::info('Role created successfully', ['role_id' => $newRole->getId()]);
        } catch (\Exception $e) {
            $this->error = 'Failed to create role: ' . $e->getMessage();
            Log::error('Error creating role: ' . $e->getMessage());
        } finally {
            $this->loading = false;
        }
    }

    public function editRole($roleId)
    {
        $role = collect($this->roles)->firstWhere('id', $roleId);
        if ($role) {
            $this->editingRole = $roleId;
            $this->editRoleName = $role['name'];
            $this->editRoleDescription = $role['guard_name'] ?? 'web';
        }
    }

    public function updateRole()
    {
        $this->validate([
            'editRoleName' => 'required|string|max:255',
            'editRoleDescription' => 'required|string|max:500',
        ]);

        try {
            $this->initializeService();
            $this->loading = true;
            $this->error = null;
            
            $token = session('auth_token');
            $updatedRole = $this->rolePermissionService->updateRole(
                $this->editingRole,
                $this->editRoleName,
                $this->editRoleDescription,
                $token
            );
            
            // Update role in local array
            $index = collect($this->roles)->search(function ($role) {
                return $role['id'] == $this->editingRole;
            });
            
            if ($index !== false) {
                $this->roles[$index]['name'] = $updatedRole->getName();
                $this->roles[$index]['guard_name'] = $updatedRole->getGuardName();
            }
            
            $this->cancelEditRole();
            session()->flash('message', 'Role updated successfully!');
            Log::info('Role updated successfully', ['role_id' => $this->editingRole]);
        } catch (\Exception $e) {
            $this->error = 'Failed to update role: ' . $e->getMessage();
            Log::error('Error updating role: ' . $e->getMessage());
        } finally {
            $this->loading = false;
        }
    }

    public function cancelEditRole()
    {
        $this->editingRole = null;
        $this->editRoleName = '';
        $this->editRoleDescription = '';
    }

    public function deleteRole($roleId)
    {
        try {
            $this->initializeService();
            $this->loading = true;
            $this->error = null;
            
            $token = session('auth_token');
            $success = $this->rolePermissionService->deleteRole($roleId, $token);
            
            if ($success) {
                // Remove role from local array
                $this->roles = collect($this->roles)->reject(function ($role) use ($roleId) {
                    return $role['id'] == $roleId;
                })->values()->toArray();
                
                session()->flash('message', 'Role deleted successfully!');
                Log::info('Role deleted successfully', ['role_id' => $roleId]);
            } else {
                $this->error = 'Failed to delete role. Please try again.';
            }
        } catch (\Exception $e) {
            $this->error = 'Failed to delete role: ' . $e->getMessage();
            Log::error('Error deleting role: ' . $e->getMessage());
        } finally {
            $this->loading = false;
        }
    }

    // Permissions Management
    public function loadPermissions()
    {
        try {
            $this->initializeService();
            $this->loading = true;
            $this->error = null;
            
            $token = session('auth_token');
            $permissionsResponse = $this->rolePermissionService->getAllPermissions($token);
            
            $this->permissions = [];
            foreach ($permissionsResponse->getPermissions() as $permission) {
                $this->permissions[] = [
                    'id' => $permission->getId(),
                    'name' => $permission->getName(),
                    'description' => $permission->getDescription(),
                    'created_at' => $permission->getCreatedAt(),
                ];
            }
            
            Log::info('Permissions loaded successfully', ['count' => count($this->permissions)]);
        } catch (\Exception $e) {
            $this->error = 'Failed to load permissions: ' . $e->getMessage();
            Log::error('Error loading permissions: ' . $e->getMessage());
            $this->permissions = [];
        } finally {
            $this->loading = false;
        }
    }

    public function createPermission()
    {
        $this->validate([
            'newPermissionName' => 'required|string|max:255',
            'newPermissionDescription' => 'required|string|max:500',
        ]);

        try {
            $this->initializeService();
            $this->loading = true;
            $this->error = null;
            
            $token = session('auth_token');
            $newPermission = $this->rolePermissionService->createPermission(
                $this->newPermissionName,
                $this->newPermissionDescription,
                $token
            );
            
            // Add the new permission to the local array
            $this->permissions[] = [
                'id' => $newPermission->getId(),
                'name' => $newPermission->getName(),
                'description' => $newPermission->getDescription(),
                'created_at' => $newPermission->getCreatedAt(),
            ];
            
            $this->newPermissionName = '';
            $this->newPermissionDescription = '';
            
            session()->flash('message', 'Permission created successfully!');
            Log::info('Permission created successfully', ['permission_id' => $newPermission->getId()]);
        } catch (\Exception $e) {
            $this->error = 'Failed to create permission: ' . $e->getMessage();
            Log::error('Error creating permission: ' . $e->getMessage());
        } finally {
            $this->loading = false;
        }
    }

    public function editPermission($permissionId)
    {
        $permission = collect($this->permissions)->firstWhere('id', $permissionId);
        if ($permission) {
            $this->editingPermission = $permissionId;
            $this->editPermissionName = $permission['name'];
            $this->editPermissionDescription = $permission['description'];
        }
    }

    public function updatePermission()
    {
        $this->validate([
            'editPermissionName' => 'required|string|max:255',
            'editPermissionDescription' => 'required|string|max:500',
        ]);

        try {
            $this->initializeService();
            $this->loading = true;
            $this->error = null;
            
            $token = session('auth_token');
            $updatedPermission = $this->rolePermissionService->updatePermission(
                $this->editingPermission,
                $this->editPermissionName,
                $this->editPermissionDescription,
                $token
            );
            
            // Update permission in local array
            $index = collect($this->permissions)->search(function ($permission) {
                return $permission['id'] == $this->editingPermission;
            });
            
            if ($index !== false) {
                $this->permissions[$index]['name'] = $updatedPermission->getName();
                $this->permissions[$index]['description'] = $updatedPermission->getDescription();
            }
            
            $this->cancelEditPermission();
            session()->flash('message', 'Permission updated successfully!');
            Log::info('Permission updated successfully', ['permission_id' => $this->editingPermission]);
        } catch (\Exception $e) {
            $this->error = 'Failed to update permission: ' . $e->getMessage();
            Log::error('Error updating permission: ' . $e->getMessage());
        } finally {
            $this->loading = false;
        }
    }

    public function cancelEditPermission()
    {
        $this->editingPermission = null;
        $this->editPermissionName = '';
        $this->editPermissionDescription = '';
    }

    public function deletePermission($permissionId)
    {
        try {
            $this->initializeService();
            $this->loading = true;
            $this->error = null;
            
            $token = session('auth_token');
            $success = $this->rolePermissionService->deletePermission($permissionId, $token);
            
            if ($success) {
                // Remove permission from local array
                $this->permissions = collect($this->permissions)->reject(function ($permission) use ($permissionId) {
                    return $permission['id'] == $permissionId;
                })->values()->toArray();
                
                session()->flash('message', 'Permission deleted successfully!');
                Log::info('Permission deleted successfully', ['permission_id' => $permissionId]);
            } else {
                $this->error = 'Failed to delete permission. Please try again.';
            }
        } catch (\Exception $e) {
            $this->error = 'Failed to delete permission: ' . $e->getMessage();
            Log::error('Error deleting permission: ' . $e->getMessage());
        } finally {
            $this->loading = false;
        }
    }

    // Role-Permission Assignment
    public function loadRolePermissions()
    {
        try {
            $this->loading = true;
            $this->error = null;
            
            $this->rolePermissions = [];
            
            // Extract permissions from roles data (permissions are already included in the roles response)
            foreach ($this->roles as $role) {
                $permissionIds = [];
                if (isset($role['permissions']) && is_array($role['permissions'])) {
                    foreach ($role['permissions'] as $permission) {
                        $permissionIds[] = $permission['id'];
                    }
                }
                $this->rolePermissions[$role['id']] = $permissionIds;
            }
            
            Log::info('Role permissions loaded successfully', ['roles_count' => count($this->rolePermissions)]);
        } catch (\Exception $e) {
            $this->error = 'Failed to load role permissions: ' . $e->getMessage();
            Log::error('Error loading role permissions: ' . $e->getMessage());
            $this->rolePermissions = [];
        } finally {
            $this->loading = false;
        }
    }

    public function selectRole($roleId)
    {
        $this->selectedRole = $roleId;
        $this->selectedPermissions = $this->rolePermissions[$roleId] ?? [];
    }

    public function togglePermission($permissionId)
    {
        if (in_array($permissionId, $this->selectedPermissions)) {
            $this->selectedPermissions = array_diff($this->selectedPermissions, [$permissionId]);
        } else {
            $this->selectedPermissions[] = $permissionId;
        }
    }

    public function saveRolePermissions()
    {
        if (!$this->selectedRole) {
            $this->error = 'Please select a role first.';
            return;
        }

        try {
            $this->initializeService();
            $this->loading = true;
            $this->error = null;
            
            $token = session('auth_token');
            $successCount = 0;
            $errorCount = 0;
            
            // Get current permissions for this role
            $currentPermissions = $this->rolePermissions[$this->selectedRole] ?? [];
            
            // Remove permissions that are no longer selected
            foreach ($currentPermissions as $permissionId) {
                if (!in_array($permissionId, $this->selectedPermissions)) {
                    try {
                        $success = $this->rolePermissionService->removePermissionFromRole(
                            $permissionId,
                            $this->selectedRole,
                            $token
                        );
                        if ($success) {
                            $successCount++;
                        } else {
                            $errorCount++;
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to remove permission from role', [
                            'permission_id' => $permissionId,
                            'role_id' => $this->selectedRole,
                            'error' => $e->getMessage()
                        ]);
                        $errorCount++;
                    }
                }
            }
            
            // Add new permissions
            foreach ($this->selectedPermissions as $permissionId) {
                if (!in_array($permissionId, $currentPermissions)) {
                    try {
                        $success = $this->rolePermissionService->assignPermissionToRole(
                            $permissionId,
                            $this->selectedRole,
                            $token
                        );
                        if ($success) {
                            $successCount++;
                        } else {
                            $errorCount++;
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to assign permission to role', [
                            'permission_id' => $permissionId,
                            'role_id' => $this->selectedRole,
                            'error' => $e->getMessage()
                        ]);
                        $errorCount++;
                    }
                }
            }
            
            // Update local state
            $this->rolePermissions[$this->selectedRole] = $this->selectedPermissions;
            
            if ($errorCount === 0) {
                session()->flash('message', 'Role permissions updated successfully!');
                Log::info('Role permissions updated successfully', [
                    'role_id' => $this->selectedRole,
                    'permissions_count' => count($this->selectedPermissions)
                ]);
            } else {
                session()->flash('message', "Role permissions updated with {$errorCount} errors. Please check the logs.");
            }
        } catch (\Exception $e) {
            $this->error = 'Failed to save role permissions: ' . $e->getMessage();
            Log::error('Error saving role permissions: ' . $e->getMessage());
        } finally {
            $this->loading = false;
        }
    }
}; ?>

<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
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
                    Processing...
                </div>
            </div>
        @endif

        <!-- Header with Refresh Button -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Roles & Permissions Management</h1>
            <button wire:click="refreshData" 
                    @if($loading) disabled @endif
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Refresh Data
            </button>
        </div>

        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8">
                <button wire:click="setActiveTab('roles')" 
                        class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'roles' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <svg class="h-5 w-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                    Roles
                </button>
                <button wire:click="setActiveTab('permissions')" 
                        class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'permissions' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <svg class="h-5 w-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    Permissions
                </button>
            </nav>
        </div>

        <!-- Roles Tab -->
        @if($activeTab === 'roles')
            <div class="space-y-6">
                <!-- Create New Role -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Create New Role</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Role Name</label>
                            <input type="text" wire:model="newRoleName" 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            @error('newRoleName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Guard Name</label>
                            <select wire:model="newRoleDescription" 
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="web">Web</option>
                                <option value="api">API</option>
                            </select>
                            @error('newRoleDescription') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="mt-4">
                        <button wire:click="createRole" 
                                @if($loading) disabled @endif
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Create Role
                        </button>
                    </div>
                </div>

                <!-- Roles List -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Existing Roles</h3>
                    </div>
                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Guard</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Permissions</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($roles as $role)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($editingRole === $role['id'])
                                                <input type="text" wire:model="editRoleName" 
                                                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            @else
                                                <div class="text-sm font-medium text-gray-900">{{ $role['name'] }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($editingRole === $role['id'])
                                                <select wire:model="editRoleDescription" 
                                                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                                    <option value="web">Web</option>
                                                    <option value="api">API</option>
                                                </select>
                                            @else
                                                <div class="text-sm text-gray-500">{{ $role['guard_name'] ?? 'web' }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $role['permissions_count'] ?? 0 }} permissions
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $role['created_at'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            @if($editingRole === $role['id'])
                                                <button wire:click="updateRole" 
                                                        class="text-green-600 hover:text-green-900 mr-3">Save</button>
                                                <button wire:click="cancelEditRole" 
                                                        class="text-gray-600 hover:text-gray-900">Cancel</button>
                                            @else
                                                <button wire:click="editRole({{ $role['id'] }})" 
                                                        class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                                <button wire:click="deleteRole({{ $role['id'] }})" 
                                                        class="text-red-600 hover:text-red-900"
                                                        onclick="return confirm('Are you sure you want to delete this role?')">Delete</button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <!-- Permissions Tab -->
        @if($activeTab === 'permissions')
            <div class="space-y-6">
                <!-- Create New Permission -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Create New Permission</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Permission Name</label>
                            <input type="text" wire:model="newPermissionName" 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            @error('newPermissionName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
<div>
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <input type="text" wire:model="newPermissionDescription" 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            @error('newPermissionDescription') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="mt-4">
                        <button wire:click="createPermission" 
                                @if($loading) disabled @endif
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Create Permission
                        </button>
                    </div>
                </div>

                <!-- Permissions List -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Existing Permissions</h3>
                    </div>
                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($permissions as $permission)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($editingPermission === $permission['id'])
                                                <input type="text" wire:model="editPermissionName" 
                                                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            @else
                                                <div class="text-sm font-medium text-gray-900">{{ $permission['name'] }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($editingPermission === $permission['id'])
                                                <input type="text" wire:model="editPermissionDescription" 
                                                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            @else
                                                <div class="text-sm text-gray-500">{{ $permission['description'] }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $permission['created_at'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            @if($editingPermission === $permission['id'])
                                                <button wire:click="updatePermission" 
                                                        class="text-green-600 hover:text-green-900 mr-3">Save</button>
                                                <button wire:click="cancelEditPermission" 
                                                        class="text-gray-600 hover:text-gray-900">Cancel</button>
                                            @else
                                                <button wire:click="editPermission({{ $permission['id'] }})" 
                                                        class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                                <button wire:click="deletePermission({{ $permission['id'] }})" 
                                                        class="text-red-600 hover:text-red-900"
                                                        onclick="return confirm('Are you sure you want to delete this permission?')">Delete</button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Role-Permission Assignment -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Assign Permissions to Roles</h3>
                    
                    <!-- Role Selection -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Role</label>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                            @foreach($roles as $role)
                                <button wire:click="selectRole({{ $role['id'] }})" 
                                        class="px-4 py-2 text-sm font-medium rounded-md border {{ $selectedRole === $role['id'] ? 'bg-blue-50 border-blue-500 text-blue-700' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                                    {{ $role['name'] }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    @if($selectedRole)
                        @php
                            $selectedRoleData = collect($roles)->firstWhere('id', $selectedRole);
                        @endphp
                        
                        <div class="mb-4">
                            <h4 class="text-md font-medium text-gray-900">Assigning permissions to: <span class="text-blue-600">{{ $selectedRoleData['name'] }}</span></h4>
                        </div>

                        <!-- Permissions Grid -->
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach($permissions as $permission)
                                <label class="relative flex items-start">
                                    <div class="flex items-center h-5">
                                        <input type="checkbox" 
                                               wire:click="togglePermission({{ $permission['id'] }})"
                                               {{ in_array($permission['id'], $selectedPermissions) ? 'checked' : '' }}
                                               class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <div class="font-medium text-gray-900">{{ $permission['name'] }}</div>
                                        <div class="text-gray-500">{{ $permission['description'] }}</div>
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            <button wire:click="saveRolePermissions" 
                                    @if($loading) disabled @endif
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Save Permissions
                            </button>
                        </div>
                    @else
                        <div class="text-center py-8 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                            <p class="mt-2">Select a role to assign permissions</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

