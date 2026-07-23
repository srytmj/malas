<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'key',
        'label',
        'icon',
        'route_name',
        'parent_key',
        'sort_order',
        'is_visible',
        'is_maintenance',
        'maintenance_message',
        'role_access',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'is_maintenance' => 'boolean',
            'role_access' => 'array',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'parent_key', 'key');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_key', 'key');
    }
}
