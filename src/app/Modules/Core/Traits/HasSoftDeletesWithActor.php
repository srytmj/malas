<?php

namespace App\Modules\Core\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;

trait HasSoftDeletesWithActor
{
    use SoftDeletes;

    public static function bootHasSoftDeletesWithActor(): void
    {
        static::deleting(function ($model) {
            if (! $model->isForceDeleting()) {
                $model->deleted_by = Auth::id();
                $model->saveQuietly();
            }
        });
    }

    public function deleteWithReason(string $reason): bool
    {
        $this->deletion_reason = $reason;
        return $this->delete();
    }
}
