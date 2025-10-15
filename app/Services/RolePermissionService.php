<?php

namespace App\Services;

use App\Http\Integrations\TelconApiConnector;
use App\Http\Integrations\Data\RoleResponse;
use App\Http\Integrations\Data\PermissionResponse;
use App\Http\Integrations\Data\RolesListResponse;
use App\Http\Integrations\Data\PermissionsListResponse;
use App\Http\Integrations\Data\UsersListResponse;
use App\Http\Integrations\Requests\GetRolesRequest;
use App\Http\Integrations\Requests\CreateRoleRequest;
use App\Http\Integrations\Requests\GetRoleRequest;
use App\Http\Integrations\Requests\UpdateRoleRequest;
use App\Http\Integrations\Requests\DeleteRoleRequest;
use App\Http\Integrations\Requests\GetUsersWithRoleRequest;
use App\Http\Integrations\Requests\AssignRoleToUserRequest;
use App\Http\Integrations\Requests\RemoveRoleFromUserRequest;
use App\Http\Integrations\Requests\GetPermissionsRequest;
use App\Http\Integrations\Requests\CreatePermissionRequest;
use App\Http\Integrations\Requests\GetPermissionRequest;
use App\Http\Integrations\Requests\UpdatePermissionRequest;
use App\Http\Integrations\Requests\DeletePermissionRequest;
use App\Http\Integrations\Requests\GetRolesWithPermissionRequest;
use App\Http\Integrations\Requests\AssignPermissionToRoleRequest;
use App\Http\Integrations\Requests\RemovePermissionFromRoleRequest;
use App\Http\Integrations\Requests\AssignPermissionToUserRequest;
use App\Http\Integrations\Requests\RemovePermissionFromUserRequest;
use Illuminate\Support\Facades\Log;

class RolePermissionService
{
    protected TelconApiConnector $connector;

    public function __construct(TelconApiConnector $connector)
    {
        $this->connector = $connector;
    }

    // Role Management Methods

    /**
     * Get all roles
     */
    public function getAllRoles(?string $token = null): RolesListResponse
    {
        try {
            $request = new GetRolesRequest($token);
            $response = $this->connector->send($request);

            // Debug: Log response details
            Log::info('GetRoles API Response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $response->body(),
                'json' => $response->json()
            ]);

            if ($response->successful()) {
                return RolesListResponse::fromResponse($response);
            }

            throw new \Exception('Failed to fetch roles: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Error fetching roles: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a new role
     */
    public function createRole(string $name, string $description, ?string $token = null): RoleResponse
    {
        try {
            $request = new CreateRoleRequest($name, $description, $token);
            $response = $this->connector->send($request);

            if ($response->successful()) {
                return RoleResponse::fromResponse($response);
            }

            throw new \Exception('Failed to create role: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Error creating role: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get a specific role by ID
     */
    public function getRole(int $roleId, ?string $token = null): RoleResponse
    {
        try {
            $request = new GetRoleRequest($roleId, $token);
            $response = $this->connector->send($request);

            if ($response->successful()) {
                return RoleResponse::fromResponse($response);
            }

            throw new \Exception('Failed to fetch role: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Error fetching role: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update a role
     */
    public function updateRole(int $roleId, string $name, string $description, ?string $token = null): RoleResponse
    {
        try {
            $request = new UpdateRoleRequest($roleId, $name, $description, $token);
            $response = $this->connector->send($request);

            if ($response->successful()) {
                return RoleResponse::fromResponse($response);
            }

            throw new \Exception('Failed to update role: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Error updating role: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a role
     */
    public function deleteRole(int $roleId, ?string $token = null): bool
    {
        try {
            $request = new DeleteRoleRequest($roleId, $token);
            $response = $this->connector->send($request);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Error deleting role: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get users with a specific role
     */
    public function getUsersWithRole(int $roleId, ?string $token = null): UsersListResponse
    {
        try {
            $request = new GetUsersWithRoleRequest($roleId, $token);
            $response = $this->connector->send($request);

            if ($response->successful()) {
                return UsersListResponse::fromResponse($response);
            }

            throw new \Exception('Failed to fetch users with role: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Error fetching users with role: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Assign role to user
     */
    public function assignRoleToUser(int $roleId, int $userId, ?string $token = null): bool
    {
        try {
            $request = new AssignRoleToUserRequest($roleId, $userId, $token);
            $response = $this->connector->send($request);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Error assigning role to user: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Remove role from user
     */
    public function removeRoleFromUser(int $roleId, int $userId, ?string $token = null): bool
    {
        try {
            $request = new RemoveRoleFromUserRequest($roleId, $userId, $token);
            $response = $this->connector->send($request);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Error removing role from user: ' . $e->getMessage());
            throw $e;
        }
    }

    // Permission Management Methods

    /**
     * Get all permissions
     */
    public function getAllPermissions(?string $token = null): PermissionsListResponse
    {
        try {
            $request = new GetPermissionsRequest($token);
            $response = $this->connector->send($request);

            if ($response->successful()) {
                return PermissionsListResponse::fromResponse($response);
            }

            throw new \Exception('Failed to fetch permissions: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Error fetching permissions: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a new permission
     */
    public function createPermission(string $name, string $description, ?string $token = null): PermissionResponse
    {
        try {
            $request = new CreatePermissionRequest($name, $description, $token);
            $response = $this->connector->send($request);

            if ($response->successful()) {
                return PermissionResponse::fromResponse($response);
            }

            throw new \Exception('Failed to create permission: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Error creating permission: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get a specific permission by ID
     */
    public function getPermission(int $permissionId, ?string $token = null): PermissionResponse
    {
        try {
            $request = new GetPermissionRequest($permissionId, $token);
            $response = $this->connector->send($request);

            if ($response->successful()) {
                return PermissionResponse::fromResponse($response);
            }

            throw new \Exception('Failed to fetch permission: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Error fetching permission: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update a permission
     */
    public function updatePermission(int $permissionId, string $name, string $description, ?string $token = null): PermissionResponse
    {
        try {
            $request = new UpdatePermissionRequest($permissionId, $name, $description, $token);
            $response = $this->connector->send($request);

            if ($response->successful()) {
                return PermissionResponse::fromResponse($response);
            }

            throw new \Exception('Failed to update permission: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Error updating permission: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a permission
     */
    public function deletePermission(int $permissionId, ?string $token = null): bool
    {
        try {
            $request = new DeletePermissionRequest($permissionId, $token);
            $response = $this->connector->send($request);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Error deleting permission: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get roles with a specific permission
     */
    public function getRolesWithPermission(int $permissionId, ?string $token = null): RolesListResponse
    {
        try {
            $request = new GetRolesWithPermissionRequest($permissionId, $token);
            $response = $this->connector->send($request);

            if ($response->successful()) {
                return RolesListResponse::fromResponse($response);
            }

            throw new \Exception('Failed to fetch roles with permission: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Error fetching roles with permission: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Assign permission to role
     */
    public function assignPermissionToRole(int $permissionId, int $roleId, ?string $token = null): bool
    {
        try {
            $request = new AssignPermissionToRoleRequest($permissionId, $roleId, $token);
            $response = $this->connector->send($request);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Error assigning permission to role: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Remove permission from role
     */
    public function removePermissionFromRole(int $permissionId, int $roleId, ?string $token = null): bool
    {
        try {
            $request = new RemovePermissionFromRoleRequest($permissionId, $roleId, $token);
            $response = $this->connector->send($request);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Error removing permission from role: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Assign permission to user
     */
    public function assignPermissionToUser(int $permissionId, int $userId, ?string $token = null): bool
    {
        try {
            $request = new AssignPermissionToUserRequest($permissionId, $userId, $token);
            $response = $this->connector->send($request);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Error assigning permission to user: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Remove permission from user
     */
    public function removePermissionFromUser(int $permissionId, int $userId, ?string $token = null): bool
    {
        try {
            $request = new RemovePermissionFromUserRequest($permissionId, $userId, $token);
            $response = $this->connector->send($request);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Error removing permission from user: ' . $e->getMessage());
            throw $e;
        }
    }
}
