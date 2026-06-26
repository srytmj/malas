<?php

use App\Http\Controllers\Admin\SeriesController as AdminSeriesController;
use App\Http\Controllers\Admin\VolumeController as AdminVolumeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin'       => Route::has('login'),
        'canRegister'    => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion'     => PHP_VERSION,
    ]);
});

// Banned page — auth required so ban_reason is available, but not_banned skipped
Route::get('/banned', function () {
    return Inertia::render('Auth/Banned');
})->middleware('auth')->name('banned');

// User area
Route::middleware(['auth', 'not_banned', 'check.menu'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});

// Profile (auth only, not a menu-guarded route)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin area
Route::prefix('admin')->name('admin.')->middleware(['auth', 'not_banned', 'check.menu'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('dashboard');

    Route::resource('series', AdminSeriesController::class);
    Route::resource('series.volumes', AdminVolumeController::class)
        ->shallow()
        ->except(['index', 'show', 'create']);
});

require __DIR__.'/auth.php';
