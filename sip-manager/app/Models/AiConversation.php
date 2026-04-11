<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiConversation extends Model
{
    protected $fillable = [
        'call_id', 'caller_number', 'called_number',
        'model', 'voice', 'prompt',
        'duration_sec', 'turns', 'cost_estimated',
        'transcript', 'hangup_reason',
    ];

    protected $casts = [
        'transcript' => 'array',
        'cost_estimated' => 'decimal:4',
    ];
}
