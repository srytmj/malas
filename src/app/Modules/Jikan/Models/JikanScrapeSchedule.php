<?php

namespace App\Modules\Jikan\Models;

use Illuminate\Database\Eloquent\Model;

class JikanScrapeSchedule extends Model
{
    protected $guarded = [];

    protected $table = 'jikan_scrape_schedule';

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'last_run_at' => 'datetime',
        ];
    }

    public static function config(): self
    {
        return self::firstOrCreate([], [
            'is_active' => false,
            'hour'      => 3,
            'minute'    => 0,
            'max_pages' => 200,
        ]);
    }
}
