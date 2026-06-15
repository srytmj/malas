<?php

namespace App\Models;

use App\Traits\HasSoftDeleteReason;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Collection extends Model
{
    use HasSoftDeleteReason, HasUuids, LogsActivity, SoftDeletes;

    protected $fillable = [
        'series_id',
        'edition_id',
        'condition',
        'price_per_volume',
        'location',
        'acquired_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'acquired_at' => 'date',
            'price_per_volume' => 'decimal:2',
            'deleted_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'collection_user');
    }

    public function volumes(): BelongsToMany
    {
        return $this->belongsToMany(Volume::class, 'collection_volumes')
                    ->withPivot('is_owned');
    }

    public function ownedVolumes(): BelongsToMany
    {
        return $this->belongsToMany(Volume::class, 'collection_volumes')
                    ->wherePivot('is_owned', true);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function getEffectivePricePerVolume(): float
    {
        return (float) ($this->price_per_volume ?? $this->series?->price_per_volume ?? 0);
    }

    public function getEstimatedWorth(): float
    {
        return $this->getEffectivePricePerVolume() * $this->ownedVolumes()->count();
    }
}
