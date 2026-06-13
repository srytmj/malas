<?php

namespace App\Modules\Core\Models;

use App\Modules\Core\Traits\HasSoftDeletesWithActor;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasUuids, HasSoftDeletesWithActor, Notifiable, HasApiTokens;

    protected $guarded = [];


    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_banned'         => 'boolean',
            'banned_at'         => 'datetime',
        ];
    }

    public function isSuperAdmin(): bool { return $this->role === 'super_admin'; }
    public function isBanned(): bool { return $this->is_banned === true; }

    public function ban(string $reason, ?string $bannedBy = null): void
    {
        $this->update(['is_banned' => true, 'banned_at' => now(), 'ban_reason' => $reason]);
        $this->tokens()->delete();
        ActivityLog::create([
            'user_id' => $bannedBy ?? $this->id, 'action' => 'user.banned',
            'entity_type' => 'user', 'entity_id' => $this->id, 'reason' => $reason,
        ]);
    }

    public function unban(?string $unbannedBy = null): void
    {
        $this->update(['is_banned' => false, 'banned_at' => null, 'ban_reason' => null]);
        ActivityLog::create([
            'user_id' => $unbannedBy ?? $this->id, 'action' => 'user.unbanned',
            'entity_type' => 'user', 'entity_id' => $this->id,
        ]);
    }

    public function library(): HasMany
    {
        return $this->hasMany(\App\Modules\Collection\Models\UserLibrary::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(\App\Modules\Collection\Models\Loan::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
