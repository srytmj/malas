<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Volume extends Model
{
    use HasUuids, LogsActivity, SoftDeletes;

    protected $fillable = [
        'series_id',
        'volume_number',
        'isbn',
        'cover_path',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'date',
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

    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class);
    }
}
