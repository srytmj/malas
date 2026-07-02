<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollectionVolume extends Model
{
    use HasUuids;

    protected $fillable = [
        'collection_id',
        'volume_number',
        'format',
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'collection_volume_id');
    }

    public function activeLoans(): HasMany
    {
        return $this->hasMany(Loan::class, 'collection_volume_id')->whereNull('returned_at');
    }
}
