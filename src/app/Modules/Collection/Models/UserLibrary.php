<?php

namespace App\Modules\Collection\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserLibrary extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $table = 'user_libraries';

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\User::class);
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Series::class);
    }

    public function collections(): HasMany
    {
        return $this->hasMany(UserCollection::class);
    }
}
