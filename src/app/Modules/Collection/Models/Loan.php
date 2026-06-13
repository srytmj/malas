<?php

namespace App\Modules\Collection\Models;

use App\Modules\Core\Traits\HasSoftDeletesWithActor;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    use HasUuids, HasSoftDeletesWithActor;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'loan_date'   => 'date',
            'due_date'    => 'date',
            'return_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(LoanItem::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(LoanEvent::class);
    }

    public function isOverdue(): bool
    {
        return $this->due_date?->isPast()
            && in_array($this->status, ['active', 'overdue']);
    }
}
