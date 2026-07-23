<?php

namespace App\Services;

use App\Models\StorageSetting;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class StorageSettingsService
{
    private const CACHE_KEY = 'storage_settings.active';

    public function settings(): StorageSetting
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return StorageSetting::first() ?? new StorageSetting(['driver' => 'local']);
        });
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function disk(): Filesystem
    {
        $settings = $this->settings();

        if ($settings->driver === 's3') {
            return $this->buildS3Disk([
                'access_key_id' => $settings->access_key_id,
                'secret_access_key' => $settings->secret_access_key,
                'bucket' => $settings->bucket,
                'endpoint' => $settings->endpoint,
                'region' => $settings->region,
                'url' => $settings->url,
            ]);
        }

        return Storage::disk('public');
    }

    /** Build an ad-hoc S3-compatible disk from raw credentials — used for the settings page's "test connection" button, before anything is saved. */
    public function buildS3Disk(array $credentials): Filesystem
    {
        return Storage::build([
            'driver' => 's3',
            'key' => $credentials['access_key_id'],
            'secret' => $credentials['secret_access_key'],
            'region' => $credentials['region'] ?: 'auto',
            'bucket' => $credentials['bucket'],
            'endpoint' => $credentials['endpoint'],
            'url' => $credentials['url'],
            'use_path_style_endpoint' => false,
            'throw' => true,
        ]);
    }

    public function url(?string $path): ?string
    {
        return $path ? $this->disk()->url($path) : null;
    }

    public function storeUploadedFile(UploadedFile $file, string $directory): string
    {
        $path = trim($directory, '/').'/'.$file->hashName();
        $this->disk()->put($path, file_get_contents($file->getRealPath()));

        return $path;
    }

    public function storeContents(string $directory, string $filename, string $contents): string
    {
        $path = trim($directory, '/').'/'.$filename;
        $this->disk()->put($path, $contents);

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path) {
            $this->disk()->delete($path);
        }
    }
}
