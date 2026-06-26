<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Volume extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'series_id',
        'volume_number',
        'cover_path',
        'type',
        'digital_source',
        'isbn',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'date',
            'deleted_at'   => 'datetime',
        ];
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'collection_volumes')
                    ->withPivot('is_owned')
                    ->withTimestamps();
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }
}
