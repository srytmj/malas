<?php

namespace App\Modules\Core\Models;

use App\Modules\Core\Traits\HasSoftDeletesWithActor;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Series extends Model
{
    use HasUuids, HasSoftDeletesWithActor;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'authors'        => 'array',
            'genres'         => 'array',
            'demographics'   => 'array',
            'published_from' => 'date',
            'published_to'   => 'date',
            'last_synced_at' => 'datetime',
        ];
    }

    public function volumes(): HasMany
    {
        return $this->hasMany(\App\Modules\Collection\Models\Volume::class);
    }

    public function userLibraries(): HasMany
    {
        return $this->hasMany(\App\Modules\Collection\Models\UserLibrary::class);
    }
}
