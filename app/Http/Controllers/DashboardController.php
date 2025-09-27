<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Auth\CheckAuthService;

class DashboardController extends Controller
{
    protected $checkAuthService;

    public function __construct(CheckAuthService $checkAuthService)
    {
        $this->checkAuthService = $checkAuthService;
    }

    /**
     * Redirect to overview page
     */
    public function index()
    {
        return redirect('/dashboard/overview');
    }

    /**
     * Show the overview page
     */
    public function overview()
    {
        if (!$this->checkAuthService->checkAuth()) {
            return redirect('/login');
        }

        return view('dashboard.overview');
    }

    /**
     * Show the products page
     */
    public function products()
    {
        if (!$this->checkAuthService->checkAuth()) {
            return redirect('/login');
        }

        return view('dashboard.products');
    }

    /**
     * Show the portfolio page
     */
    public function portfolio()
    {
        if (!$this->checkAuthService->checkAuth()) {
            return redirect('/login');
        }

        return view('dashboard.portfolio');
    }

    /**
     * Show the orders page
     */
    public function orders()
    {
        if (!$this->checkAuthService->checkAuth()) {
            return redirect('/login');
        }

        return view('dashboard.orders');
    }

    /**
     * Show the analytics page
     */
    public function analytics()
    {
        if (!$this->checkAuthService->checkAuth()) {
            return redirect('/login');
        }

        return view('dashboard.analytics');
    }

    /**
     * Show the networks page
     */
    public function networks()
    {
        if (!$this->checkAuthService->checkAuth()) {
            return redirect('/login');
        }

        return view('dashboard.networks');
    }

    /**
     * Show the settings page
     */
    public function settings()
    {
        if (!$this->checkAuthService->checkAuth()) {
            return redirect('/login');
        }

        return view('dashboard.settings');
    }

    /**
     * Switch the selected business
     */
    public function switchBusiness(Request $request)
    {
        if (!$this->checkAuthService->checkAuth()) {
            return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        $businessName = $request->input('business_name');
        $businesses = session('user_businesses', []);

        if (empty($businessName) || empty($businesses)) {
            return response()->json(['success' => false, 'message' => 'Invalid business selection'], 400);
        }

        // Find the selected business
        $selectedBusiness = collect($businesses)->firstWhere('name', $businessName);

        if (!$selectedBusiness) {
            return response()->json(['success' => false, 'message' => 'Business not found'], 404);
        }

        // Store the selected business in session
        session(['selected_business' => $selectedBusiness]);

        return response()->json([
            'success' => true, 
            'message' => 'Business switched successfully',
            'business' => $selectedBusiness
        ]);
    }
}
