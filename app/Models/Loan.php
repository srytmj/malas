<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Loan extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'collection_id',
        'collection_volume_id',
        'borrower_name',
        'loaned_at',
        'due_at',
        'returned_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'loaned_at'   => 'date',
            'due_at'      => 'date',
            'returned_at' => 'date',
        ];
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function collectionVolume(): BelongsTo
    {
        return $this->belongsTo(CollectionVolume::class, 'collection_volume_id');
    }

    public function isOverdue(): bool
    {
        return is_null($this->returned_at)
            && $this->due_at !== null
            && $this->due_at->isPast();
    }
}
