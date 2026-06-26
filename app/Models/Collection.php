<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Collection extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'series_id',
        'condition',
        'acquired_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'acquired_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }

    public function volumes(): BelongsToMany
    {
        return $this->belongsToMany(Volume::class, 'collection_volumes')
                    ->withPivot('is_owned')
                    ->withTimestamps();
    }

    public function ownedVolumes(): BelongsToMany
    {
        return $this->belongsToMany(Volume::class, 'collection_volumes')
                    ->wherePivot('is_owned', true)
                    ->withTimestamps();
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }
}
