<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ServiceController;

// Home route - redirect to login
Route::get('/', [AuthController::class, 'home']);

// Auth Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// Dashboard Routes
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

// Dashboard Component Routes
Route::get('/dashboard/networks', [DashboardController::class, 'networks'])->name('dashboard.networks');
Route::get('/dashboard/transactions', [DashboardController::class, 'transactions'])->name('dashboard.transactions');
Route::get('/dashboard/customers', [DashboardController::class, 'customers'])->name('dashboard.customers');
Route::get('/dashboard/reports', [DashboardController::class, 'reports'])->name('dashboard.reports');
Route::get('/dashboard/analytics', [DashboardController::class, 'analytics'])->name('dashboard.analytics');
Route::get('/dashboard/settings', [DashboardController::class, 'settings'])->name('dashboard.settings');
Route::get('/dashboard/inventory', [DashboardController::class, 'inventory'])->name('dashboard.inventory');

// Business switching route
Route::post('/dashboard/switch-business', [DashboardController::class, 'switchBusiness'])->name('dashboard.switch-business');

Route::get('/dashboard/perform-service', [ServiceController::class, 'performService'])->name('dashboard.perform-service');
