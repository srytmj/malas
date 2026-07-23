<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
            'deleted_at' => 'datetime',
        ];
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }
}
