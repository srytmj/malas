<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeriesMedia extends Model
{
    use HasUuids;

    protected $fillable = [
        'series_id',
        'image_path',
        'caption',
        'sort_order',
    ];

    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }
}
