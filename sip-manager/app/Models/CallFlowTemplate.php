<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallFlowTemplate extends Model
{
    protected $fillable = [
        'name', 'description', 'icon', 'steps', 'is_system', 'created_by',
    ];

    protected $casts = [
        'steps'     => 'array',
        'is_system' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
