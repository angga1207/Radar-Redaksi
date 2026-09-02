<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['actor_id', 'event', 'subject_type', 'subject_id', 'old_values', 'new_values', 'ip_address', 'user_agent'])]
class AuditLog extends Model
{
    protected function casts(): array
    {
        return ['old_values' => 'array', 'new_values' => 'array'];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** @param array<string, mixed> $oldValues @param array<string, mixed> $newValues */
    public static function record(string $event, Model $subject, array $oldValues = [], array $newValues = []): self
    {
        return self::query()->create(['actor_id' => auth()->id(), 'event' => $event, 'subject_type' => $subject->getMorphClass(), 'subject_id' => $subject->getKey(), 'old_values' => $oldValues ?: null, 'new_values' => $newValues ?: null, 'ip_address' => request()->ip(), 'user_agent' => request()->userAgent()]);
    }
}
