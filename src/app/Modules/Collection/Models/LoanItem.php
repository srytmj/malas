<?php

namespace App\Modules\Collection\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanItem extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['returned_at' => 'datetime'];
    }

    public function loan(): BelongsTo { return $this->belongsTo(Loan::class); }
    public function userCollection(): BelongsTo { return $this->belongsTo(UserCollection::class); }
    public function isReturned(): bool { return $this->returned_at !== null; }
}
