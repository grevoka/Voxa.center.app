<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallLog extends Model
{
    protected $fillable = [
        'uniqueid', 'src', 'dst', 'src_name', 'context',
        'channel', 'dst_channel', 'direction', 'trunk_name',
        'disposition', 'duration', 'billsec',
        'started_at', 'answered_at', 'ended_at',
        'recording_file', 'hangup_cause', 'extra',
    ];

    protected $casts = [
        'started_at'  => 'datetime',
        'answered_at' => 'datetime',
        'ended_at'    => 'datetime',
        'duration'    => 'integer',
        'billsec'     => 'integer',
        'extra'       => 'array',
    ];

    public function getFormattedDurationAttribute(): string
    {
        $s = $this->billsec;
        if ($s < 60) return "{$s}s";
        $m = intdiv($s, 60);
        $r = $s % 60;
        return $r > 0 ? "{$m}m {$r}s" : "{$m}m";
    }

    public function getDispositionColorAttribute(): string
    {
        return match ($this->disposition) {
            'ANSWERED'   => 'online',
            'BUSY'       => 'busy',
            'NO ANSWER'  => 'offline',
            'FAILED', 'CONGESTION' => 'error',
            default      => 'offline',
        };
    }

    public function getDispositionLabelAttribute(): string
    {
        return match ($this->disposition) {
            'ANSWERED'   => 'Repondu',
            'BUSY'       => 'Occupe',
            'NO ANSWER'  => 'Sans reponse',
            'FAILED'     => 'Echoue',
            'CONGESTION' => 'Congestion',
            default      => $this->disposition,
        };
    }

    public function getDirectionIconAttribute(): string
    {
        return match ($this->direction) {
            'inbound'  => 'bi-telephone-inbound-fill',
            'outbound' => 'bi-telephone-outbound-fill',
            default    => 'bi-telephone-fill',
        };
    }

    public function getDirectionColorAttribute(): string
    {
        return match ($this->direction) {
            'inbound'  => '#58a6ff',
            'outbound' => '#d29922',
            default    => 'var(--accent)',
        };
    }
}
