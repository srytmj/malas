<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Edition extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'language', 'publisher'];

    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class);
    }
}
