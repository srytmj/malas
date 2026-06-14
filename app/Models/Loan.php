<?php

namespace App\Models;

use App\Traits\HasSoftDeleteReason;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Loan extends Model
{
    use HasSoftDeleteReason, HasUuids, LogsActivity, SoftDeletes;

    protected $fillable = [
        'collection_id',
        'borrower_user_id',
        'borrower_name',
        'borrower_contact',
        'status',
        'loaned_at',
        'due_date',
        'returned_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'loaned_at' => 'datetime',
            'due_date' => 'date',
            'returned_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'loaned_at', 'due_date', 'returned_at', 'notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function borrower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'borrower_user_id');
    }

    public function isOverdue(): bool
    {
        return $this->status === 'active' && $this->due_date->isPast();
    }
}
