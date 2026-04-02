<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'event', 'details', 'entity_type', 'entity_id',
        'level', 'user_id', 'ip_address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(
        string $event,
        string $details = '',
        string $level = 'info',
        ?Model $entity = null,
    ): static {
        return static::create([
            'event'       => $event,
            'details'     => $details,
            'level'       => $level,
            'entity_type' => $entity ? class_basename($entity) : null,
            'entity_id'   => $entity?->id,
            'user_id'     => auth()->id(),
            'ip_address'  => request()->ip(),
        ]);
    }
}
