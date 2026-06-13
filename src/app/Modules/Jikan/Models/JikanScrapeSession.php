<?php

namespace App\Modules\Jikan\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JikanScrapeSession extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $table = 'jikan_scrape_sessions';

    protected function casts(): array
    {
        return [
            'started_at'   => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function progressPercent(): int
    {
        if ($this->total_pages === 0) return 0;
        return (int) round(($this->current_page / $this->total_pages) * 100);
    }

    public function isRunning(): bool
    {
        return in_array($this->status, ['pending', 'running']);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(JikanSchedule::class, 'schedule_id');
    }

    /** Active session that is actually processing (pending or running) */
    public static function runningSession(): ?self
    {
        return self::whereIn('status', ['pending', 'running'])->latest()->first();
    }

    /** Any non-completed session including queued ones */
    public static function activeSession(): ?self
    {
        return self::whereIn('status', ['pending', 'queued', 'running'])->latest()->first();
    }
}
