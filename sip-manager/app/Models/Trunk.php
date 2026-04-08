<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class Trunk extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'type', 'transport', 'host', 'port',
        'username', 'secret', 'max_channels', 'codecs',
        'caller_id', 'context', 'inbound_ips', 'inbound_context',
        'outbound_proxy',
        'status', 'register',
        'retry_interval', 'expiration', 'notes', 'created_by',
    ];

    protected $casts = [
        'codecs'      => 'array',
        'inbound_ips' => 'array',
        'register'    => 'boolean',
        'port'        => 'integer',
    ];

    protected $hidden = ['secret'];

    public function setSecretAttribute($value): void
    {
        if ($value) {
            $this->attributes['secret'] = Crypt::encryptString($value);
        }
    }

    public function getDecryptedSecretAttribute(): string
    {
        return $this->attributes['secret']
            ? Crypt::decryptString($this->attributes['secret'])
            : '';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getAsteriskEndpointId(): string
    {
        return 'trunk-' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $this->name));
    }

    public function getInboundEndpointId(): string
    {
        return $this->getAsteriskEndpointId() . '-in';
    }

    public function getEffectiveInboundContext(): string
    {
        return $this->inbound_context ?: 'from-trunk-' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $this->name));
    }

    public function getTransportKey(): string
    {
        return 'transport-' . strtolower($this->transport);
    }

    public function getServerUri(): string
    {
        // When outbound_proxy is set, don't include port in server_uri
        // (the domain may be a virtual SIP domain, not DNS-resolvable)
        if ($this->outbound_proxy) {
            return "sip:{$this->host}";
        }
        return "sip:{$this->host}:{$this->port}";
    }

    public function getClientUri(): string
    {
        if ($this->outbound_proxy) {
            return "sip:{$this->username}@{$this->host}";
        }
        return "sip:{$this->username}@{$this->host}:{$this->port}";
    }
}
