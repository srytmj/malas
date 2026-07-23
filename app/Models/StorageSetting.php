<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class StorageSetting extends Model
{
    use HasUuids;

    protected $fillable = [
        'driver',
        'access_key_id',
        'secret_access_key',
        'bucket',
        'endpoint',
        'region',
        'url',
    ];

    protected function casts(): array
    {
        return [
            'secret_access_key' => 'encrypted',
        ];
    }
}
