<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConferenceRoom extends Model
{
    protected $fillable = [
        'name', 'display_name', 'conference_number', 'pin', 'admin_pin',
        'max_members', 'music_on_hold', 'record', 'mute_on_join',
        'announce_join_leave', 'wait_for_leader', 'enabled', 'created_by',
    ];

    protected $casts = [
        'max_members'        => 'integer',
        'record'             => 'boolean',
        'mute_on_join'       => 'boolean',
        'announce_join_leave' => 'boolean',
        'wait_for_leader'    => 'boolean',
        'enabled'            => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Generate confbridge.conf bridge profile section.
     */
    public function toBridgeProfile(): string
    {
        $lines = [];
        $lines[] = "[bridge-{$this->name}]";
        $lines[] = "type = bridge";
        $lines[] = "max_members = {$this->max_members}";
        if ($this->record) {
            $lines[] = "record_conference = yes";
            $lines[] = "record_file = conf-{$this->name}-\${EPOCH}";
        }
        if ($this->music_on_hold !== 'default') {
            $lines[] = "music_on_hold_class = {$this->music_on_hold}";
        }

        return implode("\n", $lines);
    }

    /**
     * Generate confbridge.conf user profile for normal participants.
     */
    public function toUserProfile(): string
    {
        $lines = [];
        $lines[] = "[user-{$this->name}]";
        $lines[] = "type = user";
        $lines[] = "announce_join_leave = " . ($this->announce_join_leave ? 'yes' : 'no');
        if ($this->mute_on_join) {
            $lines[] = "startmuted = yes";
        }
        if ($this->wait_for_leader) {
            $lines[] = "wait_marked = yes";
        }
        $lines[] = "music_on_hold_when_empty = yes";
        $lines[] = "dtmf_passthrough = no";

        return implode("\n", $lines);
    }

    /**
     * Generate confbridge.conf admin user profile.
     */
    public function toAdminProfile(): string
    {
        $lines = [];
        $lines[] = "[admin-{$this->name}]";
        $lines[] = "type = user";
        $lines[] = "admin = yes";
        $lines[] = "marked = yes";
        $lines[] = "announce_join_leave = yes";
        $lines[] = "dtmf_passthrough = no";

        return implode("\n", $lines);
    }

    /**
     * Generate dialplan for this conference room.
     */
    public function toDialplan(): string
    {
        $num = $this->conference_number;
        $lines = [];

        $label = $this->display_name ?: $this->name;
        $lines[] = "exten => {$num},1,NoOp(=== Conference [{$label}] ===)";
        $lines[] = " same => n,Answer()";
        $lines[] = " same => n,Wait(1)";

        if ($this->admin_pin && $this->pin) {
            // Two PINs: try admin first, then user
            $lines[] = " same => n,Set(CONFBRIDGE_RESULT=)";
            $lines[] = " same => n,Read(CONF_PIN,conf-getpin&beep,,,,)";
            $lines[] = " same => n,GotoIf(\$[\"\${CONF_PIN}\" = \"{$this->admin_pin}\"]?admin)";
            $lines[] = " same => n,GotoIf(\$[\"\${CONF_PIN}\" = \"{$this->pin}\"]?user)";
            $lines[] = " same => n,Playback(conf-invalidpin)";
            $lines[] = " same => n,Goto({$num},1)";
            $lines[] = " same => n(admin),ConfBridge({$this->name},bridge-{$this->name},admin-{$this->name})";
            $lines[] = " same => n,Hangup()";
            $lines[] = " same => n(user),ConfBridge({$this->name},bridge-{$this->name},user-{$this->name})";
            $lines[] = " same => n,Hangup()";
        } elseif ($this->pin) {
            // Single PIN for all
            $lines[] = " same => n,Authenticate({$this->pin})";
            $lines[] = " same => n,ConfBridge({$this->name},bridge-{$this->name},user-{$this->name})";
            $lines[] = " same => n,Hangup()";
        } else {
            // No PIN — direct entry
            $lines[] = " same => n,ConfBridge({$this->name},bridge-{$this->name},user-{$this->name})";
            $lines[] = " same => n,Hangup()";
        }

        return implode("\n", $lines);
    }
}
