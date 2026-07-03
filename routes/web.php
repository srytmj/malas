<?php

use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Admin\CollectionController as AdminCollectionController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\JikanController;
use App\Http\Controllers\Admin\LoanController as AdminLoanController;
use App\Http\Controllers\Admin\MenuController as AdminMenuController;
use App\Http\Controllers\Admin\SeriesController as AdminSeriesController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VolumeController as AdminVolumeController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\User\CollectionController;
use App\Http\Controllers\User\DashboardController as UserDashboardController;
use App\Http\Controllers\User\LoanController;
use App\Http\Controllers\User\SeriesController as UserSeriesController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $user = auth()->user();
    return Inertia::render('Welcome', [
        'canLogin'       => Route::has('login'),
        'canRegister'    => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion'     => PHP_VERSION,
        'dashboardUrl'   => $user
            ? ($user->isAdmin() ? route('admin.dashboard') : route('dashboard'))
            : route('dashboard'),
    ]);
});

// Banned page — auth required so ban_reason is available, but not_banned skipped
Route::get('/banned', function () {
    return Inertia::render('Auth/Banned');
})->middleware('auth')->name('banned');

// User area
Route::middleware(['auth', 'not_banned', 'check.menu'])->group(function () {
    Route::get('/dashboard', [UserDashboardController::class, 'index'])->name('dashboard');

    // Katalog (baca saja)
    Route::get('/catalog', [UserSeriesController::class, 'index'])->name('catalog.index');
    Route::get('/catalog/search', [UserSeriesController::class, 'search'])->name('catalog.search');
    Route::get('/catalog/{series}', [UserSeriesController::class, 'show'])->name('catalog.show');

    // Koleksi pribadi
    Route::get('/my-collection', [CollectionController::class, 'index'])->name('collection.index');
    Route::post('/my-collection', [CollectionController::class, 'store'])->name('collection.store');
    Route::get('/my-collection/{collection}', [CollectionController::class, 'show'])->name('collection.show');
    Route::delete('/my-collection/{collection}', [CollectionController::class, 'destroy'])->name('collection.destroy');
    Route::post('/my-collection/{collection}/volumes', [CollectionController::class, 'storeVolumes'])->name('collection.volumes.store');
    Route::delete('/my-collection/{collection}/volumes/{collectionVolume}', [CollectionController::class, 'destroyVolume'])->name('collection.volumes.destroy');

    // Pinjaman user
    Route::get('/my-loans', [LoanController::class, 'index'])->name('loans.index');
    Route::post('/my-collection/{collection}/loans', [LoanController::class, 'store'])->name('loans.store');
    Route::put('/loans/{loan}/return', [LoanController::class, 'markReturned'])->name('loans.return');

    // Dismiss pengumuman
    Route::post('/announcements/{announcement}/dismiss', [AnnouncementController::class, 'dismiss'])->name('announcements.dismiss');

    // Pengaturan akun (semua role)
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/settings/name', [SettingsController::class, 'updateName'])->name('settings.name');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
});

// Profile (auth only, not a menu-guarded route)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin area
Route::prefix('admin')->name('admin.')->middleware(['auth', 'not_banned', 'check.menu'])->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/collections', [AdminCollectionController::class, 'index'])->name('collections.index');
    Route::get('/loans', [AdminLoanController::class, 'index'])->name('loans.index');
    Route::get('/jikan', [JikanController::class, 'index'])->name('jikan.index');
    Route::get('/jikan/search', [JikanController::class, 'searchJson'])->name('jikan.search');
    Route::get('/jikan/pictures', [JikanController::class, 'pictures'])->name('jikan.pictures');
    Route::get('/images/search', [\App\Http\Controllers\Admin\ImageSearchController::class, 'search'])->name('images.search');
    Route::post('/jikan/import', [JikanController::class, 'import'])->name('jikan.import');
    Route::resource('series', AdminSeriesController::class);
    Route::post('series/{series}/volumes/generate', [AdminVolumeController::class, 'generate'])->name('series.volumes.generate');
    Route::resource('series.volumes', AdminVolumeController::class)
        ->shallow()
        ->except(['index', 'show', 'create']);
    Route::resource('announcements', AdminAnnouncementController::class)
        ->except(['show']);

    // Users
    Route::get('/users',                    [AdminUserController::class, 'index'])      ->name('users.index');
    Route::get('/users/{user}',             [AdminUserController::class, 'show'])       ->name('users.show');
    Route::patch('/users/{user}/ban',       [AdminUserController::class, 'ban'])        ->name('users.ban');
    Route::patch('/users/{user}/unban',     [AdminUserController::class, 'unban'])      ->name('users.unban');
    Route::patch('/users/{user}/role',      [AdminUserController::class, 'changeRole']) ->name('users.role');

    // Menus
    Route::get('/menus',              [AdminMenuController::class, 'index'])  ->name('menus.index');
    Route::get('/menus/{menu}/edit',  [AdminMenuController::class, 'edit'])   ->name('menus.edit');
    Route::match(['put', 'patch'], '/menus/{menu}', [AdminMenuController::class, 'update'])->name('menus.update');
});

require __DIR__.'/auth.php';
