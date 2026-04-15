<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TrunkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $trunkId = $this->route('trunk')?->id;

        return [
            'name'       => ['required', 'string', 'max:100', Rule::unique('trunks')->ignore($trunkId)],
            'type'       => 'required|in:SIP,IAX,PRI',
            'transport'  => 'required|in:UDP,TCP,TLS',
            'host'       => 'required|string|max:255',
            'port'       => 'required|integer|min:1|max:65535',
            'username'   => 'nullable|string|max:100',
            'secret'     => $trunkId ? 'nullable|string|min:8' : 'nullable|string|min:8',
            'max_channels' => 'required|integer|min:1|max:1000',
            'codecs'     => 'nullable|array',
            'codecs.*'   => 'string|in:ulaw,alaw,g722,g729,opus,gsm,ilbc,speex',
            'caller_id'       => 'nullable|string|max:50',
            'context'         => 'nullable|string|max:50',
            'inbound_ips'     => 'nullable|array',
            'inbound_ips.*'   => 'nullable|string|max:50',
            'inbound_context' => 'nullable|string|max:50',
            'outbound_proxy'  => 'nullable|string|max:255',
            'register'        => 'boolean',
            'retry_interval' => 'nullable|integer|min:10|max:3600',
            'expiration'     => 'nullable|integer|min:60|max:86400',
            'notes'      => 'nullable|string|max:1000',
        ];
    }
}
