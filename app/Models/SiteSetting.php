<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    use HasUuids;

    protected $fillable = [
        'blur_adult_content',
    ];

    protected function casts(): array
    {
        return [
            'blur_adult_content' => 'boolean',
        ];
    }
}
