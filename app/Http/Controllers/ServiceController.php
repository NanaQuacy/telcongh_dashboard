<?php

namespace App\Http\Controllers;

use App\Services\Auth\CheckAuthService;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    protected $checkAuthService;

    public function __construct(CheckAuthService $checkAuthService)
    {
        $this->checkAuthService = $checkAuthService;
    }
    public function performService()
    {
        if (!$this->checkAuthService->checkAuth()) {
            return redirect('/login');
        }
        return view('dashboard.perform-service');
    }
}
