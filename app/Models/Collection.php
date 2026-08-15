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
        'personal_rating',
        'personal_review',
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

    public function collectionVolumes(): HasMany
    {
        return $this->hasMany(CollectionVolume::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(CollectionGroup::class, 'collection_group_items')->withTimestamps();
    }

    /**
     * Route model binding: coba cocokkan lewat slug Series-nya dulu (URL koleksi pakai judul,
     * bukan UUID) — dibatasi ke koleksi milik user yang lagi login, karena dua user bisa
     * sama-sama koleksi series yang sama (satu Series::slug bisa "punya" banyak Collection,
     * satu per user). Fallback ke id kalau nggak ketemu (link lama, atau slug punya user lain —
     * di kasus itu Policy::view() yang nolak, bukan resolveRouteBinding).
     */
    public function resolveRouteBinding($value, $field = null): ?self
    {
        return auth()->user()?->collections()
            ->whereHas('series', fn ($q) => $q->where('slug', $value))
            ->first()
            ?? static::where('id', $value)->first();
    }
}
