<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Series extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'mal_id',
        'anilist_id',
        'title_romaji',
        'title_english',
        'title_japanese',
        'synopsis',
        'cover_path',
        'status',
        'type',
        'published_from',
        'published_to',
        'total_volumes',
        'score',
        'rank',
        'genres',
        'authors',
        'themes',
        'demographics',
        'is_adult',
    ];

    protected function casts(): array
    {
        return [
            'published_from' => 'date',
            'published_to' => 'date',
            'score' => 'decimal:2',
            'deleted_at' => 'datetime',
            'genres' => 'array',
            'authors' => 'array',
            'themes' => 'array',
            'demographics' => 'array',
            'is_adult' => 'boolean',
        ];
    }

    public function volumes(): HasMany
    {
        return $this->hasMany(Volume::class);
    }

    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(SeriesMedia::class)->orderBy('sort_order');
    }
}
