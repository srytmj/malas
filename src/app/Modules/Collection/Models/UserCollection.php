<?php

namespace App\Modules\Collection\Models;

use App\Modules\Core\Traits\HasSoftDeletesWithActor;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserCollection extends Model
{
    use HasUuids, HasSoftDeletesWithActor;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'purchase_date'  => 'date',
            'purchase_price' => 'decimal:2',
            'is_for_loan'    => 'boolean',
        ];
    }

    public function userLibrary(): BelongsTo
    {
        return $this->belongsTo(UserLibrary::class);
    }

    public function volume(): BelongsTo
    {
        return $this->belongsTo(Volume::class);
    }

    public function loanItems(): HasMany
    {
        return $this->hasMany(LoanItem::class);
    }
}
