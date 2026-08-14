<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MailSetting extends Model
{
    use HasUuids;

    protected $fillable = [
        'provider',
        'api_key',
        'from_address',
        'from_name',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
        ];
    }
}
