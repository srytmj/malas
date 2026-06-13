<?php

namespace App\Modules\Jikan\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JikanSchedule extends Model
{
    protected $guarded = [];

    protected $table = 'jikan_schedules';

    protected function casts(): array
    {
        return [
            'is_active'   => 'boolean',
            'last_run_at' => 'datetime',
        ];
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(JikanScrapeSession::class, 'schedule_id');
    }

    public function yearLabel(): string
    {
        if ($this->start_year && $this->end_year) {
            return "{$this->start_year}–{$this->end_year}";
        }
        if ($this->start_year) {
            return "{$this->start_year}–sekarang";
        }
        if ($this->end_year) {
            return "s/d {$this->end_year}";
        }
        return 'Semua tahun';
    }
}
