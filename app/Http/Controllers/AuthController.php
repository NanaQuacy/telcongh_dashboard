<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Auth\CheckAuthService;
use App\Services\Auth\AuthenticationService;
use App\Http\Integrations\TelconApiConnector;

class AuthController extends Controller
{
    protected $checkAuthService;
    protected $authService;

    public function __construct(CheckAuthService $checkAuthService)
    {
        $this->checkAuthService = $checkAuthService;
        $this->authService = new AuthenticationService(new TelconApiConnector());
    }

    /**
     * Show the login page
     */
    public function showLogin()
    {
        // If user is already authenticated, redirect to dashboard
        if ($this->checkAuthService->checkAuth()) {
            return redirect('/dashboard');
        }

        return view('auth.login');
    }

    /**
     * Show the register page
     */
    public function showRegister()
    {
        // If user is already authenticated, redirect to dashboard
        if ($this->checkAuthService->checkAuth()) {
            return redirect('/dashboard');
        }

        return view('auth.register');
    }

    /**
     * Handle user logout
     */
    public function logout()
    {
        $this->authService->logout();
        return redirect('/login');
    }

    /**
     * Redirect to home page (login)
     */
    public function home()
    {
        return redirect('/login');
    }
}