<?php

namespace App\Services\Auth;

use App\Http\Integrations\TelconApiConnector;
use App\Http\Integrations\Requests\LoginRequest;
use App\Http\Integrations\Requests\RegisterRequest;
use App\Http\Integrations\Requests\LogoutRequest;
use App\Http\Integrations\Data\LoginResponse;
use App\Http\Integrations\Data\RegisterResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class AuthenticationService
{
    protected TelconApiConnector $connector;

    public function __construct(TelconApiConnector $connector)
    {
        $this->connector = $connector;
    }

    /**
     * Attempt to login with email and password
     */
    public function login(string $email, string $password, bool $remember = false): LoginResponse
    {
        try {
            $request = new LoginRequest($email, $password, $remember);
            $response = $this->connector->send($request);
            
            $loginResponse = LoginResponse::fromResponse($response);
            
            if ($loginResponse->isSuccessful()) {
                $this->storeUserSession($loginResponse);
                Log::info('User logged in successfully', ['email' => $email]);
            } else {
                Log::warning('Login attempt failed', [
                    'email' => $email,
                    'errors' => $loginResponse->getErrors()
                ]);
            }
            
            return $loginResponse;
            
        } catch (\Exception $e) {
            Log::error('Login request failed', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            
            return new LoginResponse(
                success: false,
                message: 'Login request failed. Please try again.',
                errors: ['network' => 'Unable to connect to authentication service']
            );
        }
    }

    /**
     * Store user session data
     */
    protected function storeUserSession(LoginResponse $loginResponse): void
    {
        $user = $loginResponse->getUser();
        $businesses = $loginResponse->getBusinesses();
        $roles = $loginResponse->getRoles();
        $permissions = $loginResponse->getPermissions();
        
        // Extract role names from role objects
        $roleNames = [];
        if (is_array($roles)) {
            foreach ($roles as $role) {
                if (is_array($role) && isset($role['name'])) {
                    $roleNames[] = $role['name'];
                } elseif (is_object($role) && isset($role->name)) {
                    $roleNames[] = $role->name;
                }
            }
        }
        
        // Extract permission names from permission objects
        $permissionNames = [];
        if (is_array($permissions)) {
            foreach ($permissions as $permission) {
                if (is_array($permission) && isset($permission['name'])) {
                    $permissionNames[] = $permission['name'];
                } elseif (is_object($permission) && isset($permission->name)) {
                    $permissionNames[] = $permission->name;
                }
            }
        }
        
        // Debug logging to check the data structure
        Log::info('Login response data', [
            'original_roles' => $roles,
            'original_permissions' => $permissions,
            'extracted_role_names' => $roleNames,
            'extracted_permission_names' => $permissionNames
        ]);
        
        // Log what will be stored in session
        Log::info('Session data to be stored', [
            'roles' => $roleNames,
            'permissions' => $permissionNames
        ]);
        if ($user) {
            Session::put([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'user_avatar' => $user->avatar,
                'user_role' => $user->role,
                'user_permissions' => $user->permissions,
                'auth_token' => $loginResponse->getToken(),
                'refresh_token' => $loginResponse->refreshToken,
                'authenticated' => true,
                'user_businesses' => $businesses,
                'selected_business' => $businesses[0],
                'roles' => $roleNames,
                'permissions' => $permissionNames,
            ]);
        }

        // Store businesses if available
        if ($businesses && is_array($businesses)) {
            Session::put('user_businesses', $businesses);
            
            // Set the first business as selected if available
            if (!empty($businesses)) {
                Session::put('selected_business', $businesses[0]);
            }
            
            Log::info('User businesses stored in session', [
                'user_id' => $user?->id,
                'businesses_count' => count($businesses)
            ]);
        }
    }

    /**
     * Logout the current user
     */
    public function logout(): bool
    {
        $userId = Session::get('user_id');
        $token = Session::get('auth_token');
        
        try {
            // Call API logout endpoint if we have a token
            if ($token) {
                $request = new LogoutRequest($token);
                $response = $this->connector->send($request);
                
                if (!$response->successful()) {
                    Log::warning('API logout failed', [
                        'user_id' => $userId,
                        'status' => $response->status()
                    ]);
                }
            }
            
            // Clear session data
            Session::forget([
                'user_id',
                'user_name', 
                'user_email',
                'user_avatar',
                'user_role',
                'user_permissions',
                'auth_token',
                'refresh_token',
                'authenticated',
                'user_businesses',
                'selected_business',
                'roles',
                'permissions'
            ]);
            session()->forget('user');
            session()->forget('token');
            // Clear all session data
            session()->flush();
            
            Log::info('User logged out successfully', ['user_id' => $userId]);
            return true;
            
        } catch (\Exception $e) {
            Log::error('Logout request failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            // Still clear session even if API call fails
            Session::forget([
                'user_id',
                'user_name', 
                'user_email',
                'user_avatar',
                'user_role',
                'user_permissions',
                'auth_token',
                'refresh_token',
                'authenticated',
                'roles',
                'permissions'
            ]);
            
            return false;
        }
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated(): bool
    {
        return Session::get('authenticated', false) && !empty(Session::get('auth_token'));
    }

    /**
     * Get current user data from session
     */
    public function getCurrentUser(): ?array
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return [
            'id' => Session::get('user_id'),
            'name' => Session::get('user_name'),
            'email' => Session::get('user_email'),
            'avatar' => Session::get('user_avatar'),
            'role' => Session::get('user_role'),
            'permissions' => Session::get('user_permissions'),
        ];
    }

    /**
     * Get authentication token
     */
    public function getAuthToken(): ?string
    {
        return Session::get('auth_token');
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = Session::get('user_permissions', []);
        return in_array($permission, $permissions);
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(string $role): bool
    {
        return Session::get('user_role') === $role;
    }

    /**
     * Get all user roles from session
     */
    public function getRoles(): array
    {
        return Session::get('roles', []);
    }

    /**
     * Get all user permissions from session
     */
    public function getPermissions(): array
    {
        return Session::get('permissions', []);
    }

    /**
     * Check if user has any of the specified roles
     */
    public function hasAnyRole(array $roles): bool
    {
        $userRoles = $this->getRoles();
        return !empty(array_intersect($roles, $userRoles));
    }

    /**
     * Check if user has any of the specified permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        $userPermissions = $this->getPermissions();
        return !empty(array_intersect($permissions, $userPermissions));
    }

    /**
     * Debug method to check current session state
     */
    public function debugSessionState(): array
    {
        return [
            'authenticated' => $this->isAuthenticated(),
            'roles' => $this->getRoles(),
            'permissions' => $this->getPermissions(),
            'user_role' => Session::get('user_role'),
            'user_permissions' => Session::get('user_permissions'),
        ];
    }

    /**
     * Register a new user
     */
    public function register(string $name, string $email, string $phone, string $password, string $password_confirmation, string $business_code): RegisterResponse
    {
        try {
            $request = new RegisterRequest($name, $email, $password, $password_confirmation, $phone, $business_code);
            $response = $this->connector->send($request);
            
            $registerResponse = RegisterResponse::fromResponse($response);
            
            if ($registerResponse->isSuccessful()) {
                Log::info('User registered successfully', ['email' => $email]);
            } else {
                Log::warning('Registration attempt failed', [
                    'email' => $email,
                    'errors' => $registerResponse->getErrors()
                ]);
            }
            
            return $registerResponse;
            
        } catch (\Exception $e) {
            Log::error('Registration request failed', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            
            return new RegisterResponse(
                success: false,
                message: 'Registration request failed. Please try again.',
                errors: ['network' => 'Unable to connect to authentication service']
            );
        }
    }
}
