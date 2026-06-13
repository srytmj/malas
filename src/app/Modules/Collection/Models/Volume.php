<?php

namespace App\Modules\Collection\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Volume extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function series(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Series::class);
    }

    public function userCollections(): HasMany
    {
        return $this->hasMany(UserCollection::class);
    }
}
