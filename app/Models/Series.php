<?php

namespace App\Models;

use App\Traits\HasSoftDeleteReason;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Series extends Model
{
    use HasSoftDeleteReason, HasUuids, LogsActivity, SoftDeletes;

    protected $fillable = [
        'mal_id',
        'title_romaji',
        'title_english',
        'title_japanese',
        'synopsis',
        'cover_path',
        'status',
        'published_from',
        'published_to',
        'total_volumes',
        'score',
        'rank',
        'price_per_volume',
    ];

    protected function casts(): array
    {
        return [
            'published_from' => 'date',
            'published_to' => 'date',
            'score' => 'decimal:2',
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

    public function volumes(): HasMany
    {
        return $this->hasMany(Volume::class);
    }
}
