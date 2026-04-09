<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class SipLine extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'extension', 'name', 'email', 'secret', 'protocol',
        'caller_id', 'context', 'outbound_trunk_id', 'codecs', 'status', 'transport',
        'max_contacts', 'voicemail_enabled', 'voicemail_email',
        'notes', 'created_by',
    ];

    protected $casts = [
        'codecs'            => 'array',
        'voicemail_enabled' => 'boolean',
    ];

    public function outboundTrunk(): BelongsTo
    {
        return $this->belongsTo(Trunk::class, 'outbound_trunk_id');
    }

    public function operator(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class, 'sip_line_id');
    }

    protected $hidden = ['secret'];

    public function setSecretAttribute($value): void
    {
        $this->attributes['secret'] = Crypt::encryptString($value);
    }

    public function getDecryptedSecretAttribute(): string
    {
        return Crypt::decryptString($this->attributes['secret']);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    public function getAsteriskEndpointId(): string
    {
        return $this->extension;
    }

    public function getCodecsList(): string
    {
        return $this->codecs ? implode(',', $this->codecs) : implode(',', config('asterisk.defaults.codecs'));
    }

    public function getTransportKey(): string
    {
        return match ($this->protocol) {
            'SIP/UDP' => 'transport-udp',
            'SIP/TCP' => 'transport-tcp',
            'SIP/TLS' => 'transport-tls',
            'WebRTC'  => 'transport-wss',
            default   => 'transport-udp',
        };
    }
}
