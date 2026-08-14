<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SsoFallbackToken extends Model
{
    use HasUuids;

    private const TTL_MINUTES = 15;

    protected $fillable = [
        'user_id',
        'token_hash',
        'expires_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Bikin token baru buat user, return token MENTAH (cuma ada sekarang, disimpan di link
     * email) — yang tersimpan di DB cuma hash-nya, sama seperti password_reset_tokens Laravel.
     */
    public static function issueFor(User $user): string
    {
        $raw = Str::random(64);

        static::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $raw),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        return $raw;
    }

    /** Cari token yang valid (belum expired, belum dipakai) dari token mentah di URL. */
    public static function findValid(string $rawToken): ?self
    {
        return static::query()
            ->where('token_hash', hash('sha256', $rawToken))
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    public function markUsed(): void
    {
        $this->update(['used_at' => now()]);
    }
}
