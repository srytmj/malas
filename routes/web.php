<?php

use App\Http\Controllers\Admin\CollectionController as AdminCollectionController;
use App\Http\Controllers\Admin\JikanController;
use App\Http\Controllers\Admin\LoanController as AdminLoanController;
use App\Http\Controllers\Admin\SeriesController as AdminSeriesController;
use App\Http\Controllers\Admin\VolumeController as AdminVolumeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\User\CollectionController;
use App\Http\Controllers\User\LoanController;
use App\Http\Controllers\User\SeriesController as UserSeriesController;
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

    // Katalog (baca saja)
    Route::get('/catalog', [UserSeriesController::class, 'index'])->name('catalog.index');
    Route::get('/catalog/{series}', [UserSeriesController::class, 'show'])->name('catalog.show');

    // Koleksi pribadi
    Route::get('/my-collection', [CollectionController::class, 'index'])->name('collection.index');
    Route::post('/my-collection', [CollectionController::class, 'store'])->name('collection.store');
    Route::get('/my-collection/{collection}', [CollectionController::class, 'show'])->name('collection.show');
    Route::delete('/my-collection/{collection}', [CollectionController::class, 'destroy'])->name('collection.destroy');
    Route::put('/my-collection/{collection}/volumes/{volume}', [CollectionController::class, 'toggleVolume'])->name('collection.volumes.toggle');

    // Pinjaman user
    Route::get('/my-loans', [LoanController::class, 'index'])->name('loans.index');
    Route::post('/my-collection/{collection}/loans', [LoanController::class, 'store'])->name('loans.store');
    Route::put('/loans/{loan}/return', [LoanController::class, 'markReturned'])->name('loans.return');
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

    Route::get('/collections', [AdminCollectionController::class, 'index'])->name('collections.index');
    Route::get('/loans', [AdminLoanController::class, 'index'])->name('loans.index');
    Route::get('/jikan', [JikanController::class, 'index'])->name('jikan.index');
    Route::post('/jikan/import', [JikanController::class, 'import'])->name('jikan.import');
    Route::resource('series', AdminSeriesController::class);
    Route::resource('series.volumes', AdminVolumeController::class)
        ->shallow()
        ->except(['index', 'show', 'create']);
});

require __DIR__.'/auth.php';
