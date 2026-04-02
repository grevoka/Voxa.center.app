<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallQueue extends Model
{
    protected $fillable = [
        'name', 'display_name', 'strategy', 'timeout', 'retry',
        'max_wait_time', 'music_on_hold', 'announce_frequency',
        'announce_holdtime', 'members', 'enabled', 'created_by',
    ];

    protected $casts = [
        'members' => 'array',
        'enabled' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static $strategies = [
        'ringall'    => 'Tous sonnent',
        'leastrecent' => 'Moins recent',
        'fewestcalls' => 'Moins d\'appels',
        'random'     => 'Aleatoire',
        'rrmemory'   => 'Round-robin',
        'linear'     => 'Lineaire',
        'wrandom'    => 'Pondere aleatoire',
    ];

    /**
     * Generate queues.conf section for this queue.
     */
    public function toQueuesConf(): string
    {
        $lines = [];
        $lines[] = "[{$this->name}]";
        $lines[] = "strategy = {$this->strategy}";
        $lines[] = "timeout = {$this->timeout}";
        $lines[] = "retry = {$this->retry}";
        $lines[] = "maxlen = 0";
        $lines[] = "wrapuptime = 0";
        $lines[] = "musicclass = {$this->music_on_hold}";

        if ($this->announce_holdtime) {
            $lines[] = "announce-holdtime = {$this->announce_holdtime}";
        }
        if ($this->announce_frequency) {
            $lines[] = "announce-frequency = {$this->announce_frequency}";
        }

        foreach ($this->members ?? [] as $member) {
            $ext = $member['extension'] ?? '';
            $penalty = $member['penalty'] ?? 0;
            if ($ext) {
                $lines[] = "member => PJSIP/{$ext},{$penalty}";
            }
        }

        return implode("\n", $lines);
    }
}
