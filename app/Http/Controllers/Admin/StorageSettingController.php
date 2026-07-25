<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TestStorageConnectionRequest;
use App\Http\Requests\Admin\UpdateStorageSettingRequest;
use App\Jobs\MigrateStorageFilesJob;
use App\Models\ActivityLog;
use App\Models\StorageSetting;
use App\Services\StorageSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StorageSettingController extends Controller
{
    public function __construct(private StorageSettingsService $storage) {}

    public function edit(): Response
    {
        $setting = StorageSetting::first() ?? new StorageSetting(['driver' => 'local']);

        $this->authorize('view', $setting);

        return Inertia::render('Admin/Settings/Index', [
            'setting' => [
                'driver' => $setting->driver,
                'access_key_id' => $setting->access_key_id,
                'bucket' => $setting->bucket,
                'endpoint' => $setting->endpoint,
                'region' => $setting->region,
                'url' => $setting->url,
                // secret_access_key is never sent to the frontend
                'has_secret' => filled($setting->secret_access_key),
                'migration_status' => $setting->migration_status,
                'migration_message' => $setting->migration_message,
            ],
        ]);
    }

    public function update(UpdateStorageSettingRequest $request): RedirectResponse
    {
        $setting = StorageSetting::first() ?? new StorageSetting(['driver' => 'local']);

        $this->authorize('update', $setting);

        $oldConfig = $setting->only(['driver', 'access_key_id', 'secret_access_key', 'bucket', 'endpoint', 'region', 'url']);
        $data = $request->validated();

        // Keep the existing encrypted secret if the admin left it blank
        // (e.g. only changing the bucket name without re-entering the key).
        if (blank($data['secret_access_key'] ?? null)) {
            unset($data['secret_access_key']);
        }

        $setting->fill($data)->save();

        $this->storage->forgetCache();

        ActivityLog::record('storage_settings.update', "Mengubah pengaturan storage (driver: {$oldConfig['driver']} → {$setting->driver}).");

        // Lokasi file efektif berubah — migrasi otomatis di background supaya file lama tidak "hilang" dari UI.
        $locationChanged = $oldConfig['driver'] !== $setting->driver
            || $oldConfig['access_key_id'] !== $setting->access_key_id
            || $oldConfig['bucket'] !== $setting->bucket
            || $oldConfig['endpoint'] !== $setting->endpoint
            || $oldConfig['region'] !== $setting->region;

        if ($locationChanged) {
            MigrateStorageFilesJob::dispatch($oldConfig);

            return redirect()->back()->with('success', 'Pengaturan penyimpanan disimpan. File lama sedang dipindahkan ke lokasi baru di latar belakang.');
        }

        return redirect()->back()->with('success', 'Pengaturan penyimpanan berhasil disimpan.');
    }

    public function testConnection(TestStorageConnectionRequest $request): JsonResponse
    {
        $this->authorize('update', StorageSetting::first() ?? new StorageSetting(['driver' => 'local']));

        try {
            $disk = $this->storage->buildS3Disk($request->validated());
            $testPath = 'storage-test/'.uniqid('', true).'.txt';
            $disk->put($testPath, 'MALAS storage connection test');
            $disk->delete($testPath);

            return response()->json([
                'success' => true,
                'message' => 'Koneksi berhasil — kredensial valid dan bucket bisa diakses.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
