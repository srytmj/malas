<?php

use App\Modules\Admin\Http\Controllers\ActivityLogController;
use App\Modules\Admin\Http\Controllers\AdminApiController;
use App\Modules\Admin\Http\Controllers\CollectionController;
use App\Modules\Admin\Http\Controllers\JikanController;
use App\Modules\Admin\Http\Controllers\AdminDashboardController;
use App\Modules\Admin\Http\Controllers\LoanController;
use App\Modules\Admin\Http\Controllers\SeriesController;
use App\Modules\Admin\Http\Controllers\UserManagementController;
use App\Modules\Admin\Http\Controllers\VolumeController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:super_admin'])->group(function () {

    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Users
    Route::get('users/create', [UserManagementController::class, 'create'])->name('users.create');
    Route::post('users', [UserManagementController::class, 'store'])->name('users.store');
    Route::get('users', [UserManagementController::class, 'index'])->name('users.index');
    Route::get('users/{user}', [UserManagementController::class, 'show'])->name('users.show');
    Route::post('users/{user}/ban', [UserManagementController::class, 'ban'])->name('users.ban');
    Route::post('users/{user}/unban', [UserManagementController::class, 'unban'])->name('users.unban');
    Route::delete('users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');

    // Series
    Route::get('series/create', [SeriesController::class, 'create'])->name('series.create');
    Route::post('series', [SeriesController::class, 'store'])->name('series.store');
    Route::post('series/batch-destroy', [SeriesController::class, 'batchDestroy'])->name('series.batch-destroy');
    Route::post('series/destroy-all', [SeriesController::class, 'destroyAll'])->name('series.destroy-all');
    Route::get('series', [SeriesController::class, 'index'])->name('series.index');
    Route::get('series/{series}', [SeriesController::class, 'show'])->name('series.show');
    Route::get('series/{series}/edit', [SeriesController::class, 'edit'])->name('series.edit');
    Route::patch('series/{series}', [SeriesController::class, 'update'])->name('series.update');
    Route::delete('series/{series}', [SeriesController::class, 'destroy'])->name('series.destroy');

    // Volumes
    Route::get('series/{series}/volumes', [VolumeController::class, 'index'])->name('volumes.index');
    Route::post('series/{series}/volumes', [VolumeController::class, 'store'])->name('volumes.store');
    Route::get('series/{series}/volumes/{volume}/edit', [VolumeController::class, 'edit'])->name('volumes.edit');
    Route::patch('series/{series}/volumes/{volume}', [VolumeController::class, 'update'])->name('volumes.update');
    Route::delete('series/{series}/volumes/{volume}', [VolumeController::class, 'destroy'])->name('volumes.destroy');

    // Collections
    Route::get('collections/create', [CollectionController::class, 'create'])->name('collections.create');
    Route::post('collections', [CollectionController::class, 'store'])->name('collections.store');
    Route::post('collections/bulk', [CollectionController::class, 'bulkStore'])->name('collections.bulk');
    Route::get('collections', [CollectionController::class, 'index'])->name('collections.index');
    Route::get('collections/{collection}', [CollectionController::class, 'show'])->name('collections.show');
    Route::get('collections/{collection}/edit', [CollectionController::class, 'edit'])->name('collections.edit');
    Route::patch('collections/{collection}', [CollectionController::class, 'update'])->name('collections.update');
    Route::delete('collections/{collection}', [CollectionController::class, 'destroy'])->name('collections.destroy');

    // Loans
    Route::get('loans/create', [LoanController::class, 'create'])->name('loans.create');
    Route::post('loans', [LoanController::class, 'store'])->name('loans.store');
    Route::get('loans', [LoanController::class, 'index'])->name('loans.index');
    Route::get('loans/{loan}', [LoanController::class, 'show'])->name('loans.show');
    Route::get('loans/{loan}/edit', [LoanController::class, 'edit'])->name('loans.edit');
    Route::patch('loans/{loan}', [LoanController::class, 'update'])->name('loans.update');
    Route::patch('loans/{loan}/return', [LoanController::class, 'markReturned'])->name('loans.return');
    Route::patch('loans/{loan}/lost', [LoanController::class, 'markLost'])->name('loans.lost');
    Route::delete('loans/{loan}', [LoanController::class, 'destroy'])->name('loans.destroy');

    // Jikan Scraper — multi-schedule
    Route::get('jikan', [JikanController::class, 'index'])->name('jikan.index');
    Route::get('jikan/status', [JikanController::class, 'status'])->name('jikan.status');
    Route::post('jikan/scrape-now', [JikanController::class, 'scrapeNow'])->name('jikan.scrape-now');
    Route::post('jikan/schedules', [JikanController::class, 'storeSchedule'])->name('jikan.schedule.store');
    Route::patch('jikan/schedules/{schedule}', [JikanController::class, 'updateSchedule'])->name('jikan.schedule.update');
    Route::delete('jikan/schedules/{schedule}', [JikanController::class, 'destroySchedule'])->name('jikan.schedule.destroy');
    Route::post('jikan/schedules/reorder', [JikanController::class, 'reorderSchedules'])->name('jikan.schedule.reorder');
    Route::post('jikan/sessions/{session}/cancel', [JikanController::class, 'cancelSession'])->name('jikan.cancel');

    // Activity Log
    Route::get('activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');

    // Admin AJAX APIs
    Route::get('api/jikan/search', [AdminApiController::class, 'jikanSearch'])->name('api.jikan.search');
    Route::post('api/jikan/import', [AdminApiController::class, 'jikanImport'])->name('api.jikan.import');
    Route::get('api/series/search', [AdminApiController::class, 'searchSeries'])->name('api.series.search');
    Route::get('api/series/{series}/volumes', [AdminApiController::class, 'seriesVolumes'])->name('api.series.volumes');
    Route::get('api/series/{series}/user-collections', [AdminApiController::class, 'seriesUserCollections'])->name('api.series.user-collections');
    Route::get('api/series/{series}/volume-status', [AdminApiController::class, 'seriesVolumeStatus'])->name('api.series.volume-status');
});
