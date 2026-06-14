<?php

namespace App\Traits;

trait HasSoftDeleteReason
{
    public function deleteWithReason(string $reason, ?string $actorId = null): bool
    {
        $this->forceFill([
            'deleted_reason' => $reason,
            'deleted_by' => $actorId ?? auth()->id(),
        ])->saveQuietly();

        return $this->delete();
    }
}
