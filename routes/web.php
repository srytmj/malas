<?php

use App\Http\Controllers\Admin\AniListController;
use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Admin\CollectionController as AdminCollectionController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DatabaseBackupController;
use App\Http\Controllers\Admin\ImageSearchController;
use App\Http\Controllers\Admin\LoanController as AdminLoanController;
use App\Http\Controllers\Admin\MenuController as AdminMenuController;
use App\Http\Controllers\Admin\SeriesController as AdminSeriesController;
use App\Http\Controllers\Admin\StorageSettingController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VolumeController as AdminVolumeController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\Auth\SsoController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\User\CollectionController;
use App\Http\Controllers\User\DashboardController as UserDashboardController;
use App\Http\Controllers\User\LoanController;
use App\Http\Controllers\User\SeriesController as UserSeriesController;
use App\Http\Controllers\User\TicketController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $user = auth()->user();

    if (! $user) {
        return Inertia::render('Landing');
    }

    return redirect($user->isAdmin() ? route('admin.dashboard') : route('dashboard'));
});

// SSO — whitearchive.id
Route::get('/auth/redirect', [SsoController::class, 'redirect'])->name('sso.redirect');
Route::get('/auth/callback', [SsoController::class, 'callback'])->name('sso.callback');
Route::middleware('auth')->post('/logout', [SsoController::class, 'logout'])->name('logout');

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
    Route::delete('/my-collection/{collection}/volumes/bulk', [CollectionController::class, 'destroyVolumes'])->name('collection.volumes.destroyBulk');
    Route::delete('/my-collection/{collection}/volumes/{collectionVolume}', [CollectionController::class, 'destroyVolume'])->name('collection.volumes.destroy');

    // Pinjaman user
    Route::get('/my-loans', [LoanController::class, 'index'])->name('loans.index');
    Route::post('/my-collection/{collection}/loans', [LoanController::class, 'store'])->name('loans.store');
    Route::put('/loans/{loan}/return', [LoanController::class, 'markReturned'])->name('loans.return');

    // Dismiss pengumuman
    Route::post('/announcements/{announcement}/dismiss', [AnnouncementController::class, 'dismiss'])->name('announcements.dismiss');

    // Tiket ke superadmin
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');

    // Pengaturan akun (semua role) — profil dikelola di SSO, ini hanya tampilan read-only
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
});

// Admin area
Route::prefix('admin')->name('admin.')->middleware(['auth', 'not_banned', 'check.menu'])->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/collections', [AdminCollectionController::class, 'index'])->name('collections.index');
    Route::get('/loans', [AdminLoanController::class, 'index'])->name('loans.index');
    Route::get('/anilist', [AniListController::class, 'index'])->name('anilist.index');
    Route::get('/anilist/search', [AniListController::class, 'searchJson'])->name('anilist.search');
    Route::get('/anilist/status', [AniListController::class, 'statusPage'])->name('anilist.status.page');
    Route::get('/anilist/status/check', [AniListController::class, 'statusCheck'])->name('anilist.status');
    Route::get('/images/search', [ImageSearchController::class, 'search'])->name('images.search');
    Route::post('/anilist/import', [AniListController::class, 'import'])->name('anilist.import');
    Route::delete('/series/bulk', [AdminSeriesController::class, 'bulkDestroy'])->name('series.bulk-destroy');
    Route::resource('series', AdminSeriesController::class);
    Route::post('series/{series}/volumes/generate', [AdminVolumeController::class, 'generate'])->name('series.volumes.generate');
    Route::resource('series.volumes', AdminVolumeController::class)
        ->shallow()
        ->except(['index', 'show', 'create']);
    Route::resource('announcements', AdminAnnouncementController::class)
        ->except(['show']);

    // Users
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::patch('/users/{user}/ban', [AdminUserController::class, 'ban'])->name('users.ban');
    Route::patch('/users/{user}/unban', [AdminUserController::class, 'unban'])->name('users.unban');
    Route::patch('/users/{user}/role', [AdminUserController::class, 'changeRole'])->name('users.role');

    // Menus
    Route::get('/menus', [AdminMenuController::class, 'index'])->name('menus.index');
    Route::get('/menus/{menu}/edit', [AdminMenuController::class, 'edit'])->name('menus.edit');
    Route::match(['put', 'patch'], '/menus/{menu}', [AdminMenuController::class, 'update'])->name('menus.update');

    // Tiket dari user
    Route::get('/tickets', [AdminTicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [AdminTicketController::class, 'show'])->name('tickets.show');
    Route::patch('/tickets/{ticket}/respond', [AdminTicketController::class, 'respond'])->name('tickets.respond');

    // Pengaturan penyimpanan (super_admin only, ditegakkan lewat role_access menu + Policy)
    Route::get('/settings/storage', [StorageSettingController::class, 'edit'])->name('settings.storage.edit');
    Route::put('/settings/storage', [StorageSettingController::class, 'update'])->name('settings.storage.update');
    Route::post('/settings/storage/test', [StorageSettingController::class, 'testConnection'])->name('settings.storage.test');

    // Backup & import database (super_admin only)
    Route::get('/settings/database', [DatabaseBackupController::class, 'index'])->name('settings.db.index');
    Route::get('/settings/database/download', [DatabaseBackupController::class, 'download'])->name('settings.db.download');
    Route::post('/settings/database/import', [DatabaseBackupController::class, 'import'])->name('settings.db.import');
});
