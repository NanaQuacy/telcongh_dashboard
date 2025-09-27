# Saloon HTTP Client Setup

This project uses Saloon HTTP client for making API requests to the Telcon API.

## Installation

Saloon has been installed via Composer:
```bash
composer require saloonphp/saloon
```

## Configuration

Add the following environment variables to your `.env` file:

```env
# Telcon API Configuration
TELCON_API_BASE_URL=https://api.telcon.com/v1
TELCON_API_TIMEOUT=30
TELCON_API_KEY=your_api_key_here
```

## Files Created

### 1. Connector
- `app/Http/Integrations/TelconApiConnector.php` - Main API connector

### 2. Request Classes
- `app/Http/Integrations/Requests/LoginRequest.php` - Login API request

### 3. Data Transfer Objects (DTOs)
- `app/Http/Integrations/Data/LoginResponse.php` - Login response handler
- `app/Http/Integrations/Data/UserData.php` - User data structure

### 4. Services
- `app/Services/AuthenticationService.php` - Authentication business logic

### 5. Service Provider
- `app/Providers/AuthenticationServiceProvider.php` - Dependency injection setup

## Usage

The login functionality is now integrated with Livewire Volt components:

1. **Login Component**: `resources/views/livewire/auth/login.php`
2. **Login View**: `resources/views/livewire/auth/login.blade.php`

### Features

- ✅ **Real API Integration**: Uses Saloon to make HTTP requests
- ✅ **Form Validation**: Client-side and server-side validation
- ✅ **Loading States**: Shows loading spinner during requests
- ✅ **Error Handling**: Displays API errors and network issues
- ✅ **Session Management**: Stores user data in Laravel sessions
- ✅ **Responsive UI**: Mobile-friendly login form
- ✅ **Security**: CSRF protection and secure token handling

### API Endpoint

The login request hits: `POST /auth/login`

**Request Body:**
```json
{
    "email": "user@example.com",
    "password": "password123",
    "remember": false
}
```

**Success Response:**
```json
{
    "success": true,
    "data": {
        "token": "jwt_token_here",
        "refresh_token": "refresh_token_here",
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "user@example.com",
            "avatar": "https://example.com/avatar.jpg",
            "role": "user",
            "permissions": ["read", "write"]
        }
    }
}
```

**Error Response:**
```json
{
    "success": false,
    "message": "Invalid credentials",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password field is required."]
    }
}
```

## Testing

To test the login functionality:

1. Set up your `.env` file with the correct API configuration
2. Start the Laravel server: `php artisan serve`
3. Navigate to `/login`
4. Enter valid credentials to test the API integration

## Next Steps

You can extend this setup by:

1. Adding more API endpoints (register, logout, profile, etc.)
2. Implementing token refresh functionality
3. Adding request/response logging
4. Creating API rate limiting
5. Adding request retry logic
6. Implementing API caching
