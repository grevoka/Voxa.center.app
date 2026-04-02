<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallContext extends Model
{
    protected $fillable = [
        'name', 'direction', 'description', 'dial_pattern',
        'destination', 'destination_type', 'trunk_id',
        'caller_id_override', 'prefix_strip', 'prefix_add',
        'timeout', 'ring_timeout', 'record_calls',
        'voicemail_enabled', 'voicemail_box', 'greeting_sound', 'music_on_hold',
        'enabled', 'priority', 'notes', 'created_by',
    ];

    protected $casts = [
        'record_calls'      => 'boolean',
        'voicemail_enabled'  => 'boolean',
        'enabled'           => 'boolean',
        'timeout'           => 'integer',
        'ring_timeout'      => 'integer',
        'priority'          => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function trunk(): BelongsTo
    {
        return $this->belongsTo(Trunk::class, 'trunk_id');
    }

    /**
     * Generate Asterisk dialplan for this context.
     */
    public function toDialplan(): string
    {
        $lines = [];
        $pattern = $this->dial_pattern ?: '_X.';

        if ($this->direction === 'outbound' && $this->trunk_id) {
            $lines = $this->buildOutboundDialplan($pattern);
        } elseif ($this->direction === 'inbound') {
            $lines = $this->buildInboundDialplan($pattern);
        } else {
            $lines = $this->buildInternalDialplan($pattern);
        }

        return implode("\n", $lines);
    }

    private function buildOutboundDialplan(string $pattern): array
    {
        $trunk = Trunk::find($this->trunk_id);
        $trunkEndpoint = $trunk ? $trunk->getAsteriskEndpointId() : 'unknown';
        $lines = [];

        $lines[] = "exten => {$pattern},1,NoOp(Appel sortant via {$this->name})";
        $lines[] = " same => n,Set(CDR(direction)=outbound)";

        if ($this->caller_id_override) {
            $lines[] = " same => n,Set(CALLERID(num)={$this->caller_id_override})";
        }

        $dialStr = "PJSIP/\${EXTEN}@{$trunkEndpoint}";

        if ($this->prefix_strip !== null && $this->prefix_strip !== '') {
            $stripLen = strlen($this->prefix_strip);
            $lines[] = " same => n,Set(OUTNUM=\${EXTEN:{$stripLen}})";
            if ($this->prefix_add !== null && $this->prefix_add !== '') {
                $lines[] = " same => n,Set(OUTNUM={$this->prefix_add}\${OUTNUM})";
            }
            $dialStr = "PJSIP/\${OUTNUM}@{$trunkEndpoint}";
        } elseif ($this->prefix_add !== null && $this->prefix_add !== '') {
            $lines[] = " same => n,Set(OUTNUM={$this->prefix_add}\${EXTEN})";
            $dialStr = "PJSIP/\${OUTNUM}@{$trunkEndpoint}";
        }

        if ($this->record_calls) {
            $lines[] = " same => n,MixMonitor(\${UNIQUEID}.wav,b)";
        }

        $lines[] = " same => n,Dial({$dialStr},{$this->timeout},tTb(handler^addheader^1))";
        $lines[] = " same => n,Hangup()";

        return $lines;
    }

    private function buildInboundDialplan(string $pattern): array
    {
        $lines = [];

        $lines[] = "exten => {$pattern},1,NoOp(Appel entrant — contexte {$this->name})";
        $lines[] = " same => n,Set(CDR(direction)=inbound)";

        // Answer + ringing indication
        $lines[] = " same => n,Ringing()";

        // Music on hold while routing
        if ($this->music_on_hold) {
            $lines[] = " same => n,Set(CHANNEL(musicclass)={$this->music_on_hold})";
        }

        if ($this->record_calls) {
            $lines[] = " same => n,MixMonitor(\${UNIQUEID}.wav,b)";
        }

        // Custom greeting before ringing
        if ($this->greeting_sound) {
            $lines[] = " same => n,Answer()";
            $lines[] = " same => n,Wait(1)";
            $lines[] = " same => n,Playback({$this->greeting_sound})";
        }

        // Ring the destination
        $ringTimeout = $this->ring_timeout ?: 25;

        if ($this->destination_type === 'extensions') {
            $dest = $this->destination ?: '${EXTEN}';
            // If destination contains comma, ring multiple extensions
            if (str_contains($dest, ',')) {
                $endpoints = collect(explode(',', $dest))
                    ->map(fn($ext) => 'PJSIP/' . trim($ext))
                    ->implode('&');
                $lines[] = " same => n,Dial({$endpoints},{$ringTimeout},tT)";
            } else {
                $lines[] = " same => n,Dial(PJSIP/{$dest},{$ringTimeout},tT)";
            }
        } elseif ($this->destination_type === 'queue') {
            $lines[] = " same => n,Queue({$this->destination},{$ringTimeout})";
        } else {
            $lines[] = " same => n,Dial(PJSIP/\${EXTEN},{$ringTimeout},tT)";
        }

        // After ring timeout → voicemail or hangup
        if ($this->voicemail_enabled && $this->voicemail_box) {
            $vmBox = $this->voicemail_box;
            $lines[] = " same => n,GotoIf(\$[\"\${DIALSTATUS}\" = \"BUSY\"]?busy:unavail)";
            $lines[] = " same => n(unavail),VoiceMail({$vmBox},u)";
            $lines[] = " same => n,Hangup()";
            $lines[] = " same => n(busy),VoiceMail({$vmBox},b)";
            $lines[] = " same => n,Hangup()";
        } else {
            $lines[] = " same => n,Hangup()";
        }

        return $lines;
    }

    private function buildInternalDialplan(string $pattern): array
    {
        $lines = [];
        $ringTimeout = $this->ring_timeout ?: 25;

        $lines[] = "exten => {$pattern},1,NoOp(Appel interne — {$this->name})";
        $lines[] = " same => n,Set(CDR(direction)=internal)";

        if ($this->record_calls) {
            $lines[] = " same => n,MixMonitor(\${UNIQUEID}.wav,b)";
        }

        $lines[] = " same => n,Dial(PJSIP/\${EXTEN},{$ringTimeout},tT)";

        // Voicemail fallback for internal calls too
        if ($this->voicemail_enabled && $this->voicemail_box) {
            $vmBox = $this->voicemail_box;
            $lines[] = " same => n,GotoIf(\$[\"\${DIALSTATUS}\" = \"BUSY\"]?busy:unavail)";
            $lines[] = " same => n(unavail),VoiceMail({$vmBox},u)";
            $lines[] = " same => n,Hangup()";
            $lines[] = " same => n(busy),VoiceMail({$vmBox},b)";
            $lines[] = " same => n,Hangup()";
        } else {
            $lines[] = " same => n,Hangup()";
        }

        // Acces direct a la boite vocale via *98
        if ($this->name === 'from-internal') {
            $lines[] = "";
            $lines[] = "; Acces boite vocale";
            $lines[] = "exten => *98,1,NoOp(Acces boite vocale)";
            $lines[] = " same => n,VoiceMailMain(\${CALLERID(num)}@default)";
            $lines[] = " same => n,Hangup()";
        }

        return $lines;
    }

    public static function generateFullDialplan(): string
    {
        $output = "[general]\nstatic = yes\nwriteprotect = no\nclearglobalvars = no\n\n";

        // Subroutine handler for outbound
        $output .= "[handler]\n";
        $output .= "exten => addheader,1,NoOp(Adding SIP headers)\n";
        $output .= " same => n,Return()\n\n";

        $contexts = static::where('enabled', true)->orderBy('priority')->get();
        $grouped = $contexts->groupBy('name');

        foreach ($grouped as $contextName => $rules) {
            $output .= "[{$contextName}]\n";
            $direction = $rules->first()->direction;
            $output .= "; Direction: {$direction}\n";
            if ($rules->first()->description) {
                $output .= "; {$rules->first()->description}\n";
            }

            // Include outbound from internal context
            if ($direction === 'internal') {
                $outboundContexts = static::where('enabled', true)
                    ->where('direction', 'outbound')
                    ->orderBy('priority')
                    ->pluck('name')
                    ->unique();
                foreach ($outboundContexts as $outCtx) {
                    $output .= "include => {$outCtx}\n";
                }
            }

            foreach ($rules as $rule) {
                $output .= $rule->toDialplan() . "\n";
            }
            $output .= "\n";
        }

        return $output;
    }
}
