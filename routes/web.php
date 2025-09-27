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
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Dashboard Component Routes
Route::get('/dashboard/overview', [DashboardController::class, 'overview'])->name('dashboard.overview');
Route::get('/dashboard/products', [DashboardController::class, 'products'])->name('dashboard.products');
Route::get('/dashboard/portfolio', [DashboardController::class, 'portfolio'])->name('dashboard.portfolio');
Route::get('/dashboard/orders', [DashboardController::class, 'orders'])->name('dashboard.orders');
Route::get('/dashboard/analytics', [DashboardController::class, 'analytics'])->name('dashboard.analytics');
Route::get('/dashboard/settings', [DashboardController::class, 'settings'])->name('dashboard.settings');
Route::get('/dashboard/networks', [DashboardController::class, 'networks'])->name('dashboard.networks');

// Business switching route
Route::post('/dashboard/switch-business', [DashboardController::class, 'switchBusiness'])->name('dashboard.switch-business');

Route::get('/dashboard/perform-service', [ServiceController::class, 'performService'])->name('dashboard.perform-service');
