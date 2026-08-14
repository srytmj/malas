<?php

namespace App\Services;

use App\Models\MailSetting;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailSettingsService
{
    private const CACHE_KEY = 'mail_settings.active';

    public function settings(): MailSetting
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return MailSetting::first() ?? new MailSetting(['provider' => 'resend']);
        });
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function isConfigured(): bool
    {
        return filled($this->settings()->api_key);
    }

    /**
     * Kirim mailable lewat provider yang dikonfigurasi admin (bukan .env) — set config Laravel
     * secara runtime persis sebelum kirim, sama pola dengan StorageSettingsService::disk() yang
     * bangun disk S3 ad-hoc dari kredensial tersimpan di DB.
     */
    public function send(string $to, Mailable $mailable): bool
    {
        $settings = $this->settings();

        if (blank($settings->api_key)) {
            Log::warning('MailSettingsService::send dipanggil tapi belum ada API key mail terkonfigurasi.');

            return false;
        }

        config([
            'services.resend.key' => $settings->api_key,
            'mail.from.address' => $settings->from_address ?: 'noreply@example.com',
            'mail.from.name' => $settings->from_name ?: config('app.name'),
        ]);

        Mail::mailer('resend')->to($to)->send($mailable);

        return true;
    }
}
