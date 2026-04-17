<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WidgetToken extends Model
{
    protected $fillable = [
        'name', 'token', 'domain', 'callflow_id', 'extension',
        'enabled', 'max_concurrent', 'call_count', 'last_used_at', 'created_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'max_concurrent' => 'integer',
        'call_count' => 'integer',
        'last_used_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (WidgetToken $widget) {
            if (empty($widget->token)) {
                $widget->token = bin2hex(random_bytes(24)); // 48 chars
            }
        });
    }

    public function callflow(): BelongsTo
    {
        return $this->belongsTo(CallFlow::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if the given origin/referer is allowed for this token.
     * Supports exact match and wildcard prefix (*.example.com).
     */
    public function isValidForDomain(?string $origin): bool
    {
        if (!$this->enabled) return false;
        if (empty($origin)) return false;

        // Extract hostname from URL
        $host = parse_url($origin, PHP_URL_HOST) ?: $origin;
        $host = strtolower(trim($host));
        $allowed = strtolower(trim($this->domain));

        // Exact match
        if ($host === $allowed) return true;

        // Wildcard match (*.example.com)
        if (str_starts_with($allowed, '*.')) {
            $suffix = substr($allowed, 1); // .example.com
            return str_ends_with($host, $suffix) || $host === substr($allowed, 2);
        }

        return false;
    }

    /**
     * Get the token prefix used for guest endpoint IDs.
     */
    public function getTokenPrefix(): string
    {
        return substr($this->token, 0, 6);
    }

    /**
     * Get the target type for dialplan routing.
     */
    public function getTargetType(): string
    {
        return $this->callflow_id ? 'callflow' : 'extension';
    }

    /**
     * Get the target value for dialplan routing.
     */
    public function getTargetValue(): string
    {
        if ($this->callflow_id) {
            return $this->callflow?->inbound_context ?? 'from-internal';
        }
        return $this->extension ?? '1001';
    }
}
