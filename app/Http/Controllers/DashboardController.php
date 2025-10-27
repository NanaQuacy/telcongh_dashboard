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
     * Show the main dashboard page
     */
    public function index()
    {
        if (!$this->checkAuthService->checkAuth()) {
            return redirect('/login');
        }

        return view('dashboard.index');
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
     * Show the transactions page
     */
    public function transactions()
    {
        if (!$this->checkAuthService->checkAuth()) {
            return redirect('/login');
        }

        return view('dashboard.transactions');
    }

    /**
     * Show the customers page
     */
    public function customers()
    {
        if (!$this->checkAuthService->checkAuth()) {
            return redirect('/login');
        }

        return view('dashboard.customers');
    }

    /**
     * Show the reports page
     */
    public function reports()
    {
        if (!$this->checkAuthService->checkAuth()) {
            return redirect('/login');
        }

        return view('dashboard.reports');
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

    /**
     * Show the inventory page
     */
    public function inventory()
    {
        if (!$this->checkAuthService->checkAuth()) {
            return redirect('/login');
        }

        return view('dashboard.inventory');
    }

    /**
     * Show the roles page
     */
    public function roles()
    {
        if (!$this->checkAuthService->checkAuth()) {
            return redirect('/login');
        }

        return view('dashboard.roles');
    }

    /**
     * Show the users page
     */
    public function users()
    {
        if (!$this->checkAuthService->checkAuth()) {
            return redirect('/login');
        }

        return view('dashboard.users');
    }

    /**
     * Download temporary file
     */
    public function downloadTemp($filename)
    {
        if (!$this->checkAuthService->checkAuth()) {
            return redirect('/login');
        }

        $filePath = storage_path('app/temp/' . $filename);
        
        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }

        // Determine content type based on file extension
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $contentType = match($extension) {
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'pdf' => 'application/pdf',
            'csv' => 'text/csv',
            default => 'application/octet-stream'
        };

        // Download and delete the file
        return response()->download($filePath, $filename, [
            'Content-Type' => $contentType,
        ])->deleteFileAfterSend(true);
    }
}
