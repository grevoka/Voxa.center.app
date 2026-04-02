<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SipLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $lineId = $this->route('line')?->id;

        return [
            'extension'  => [
                'required', 'string', 'max:20', 'regex:/^\d+$/',
                Rule::unique('sip_lines')->ignore($lineId),
            ],
            'name'       => 'required|string|max:100',
            'email'      => 'nullable|email|max:255',
            'secret'     => $lineId ? 'nullable|string|min:8' : 'required|string|min:8',
            'protocol'   => 'required|in:SIP/UDP,SIP/TCP,SIP/TLS,WebRTC',
            'caller_id'  => 'nullable|string|max:50',
            'context'    => 'nullable|string|max:50',
            'codecs'     => 'nullable|array',
            'codecs.*'   => 'string|in:ulaw,alaw,g722,g729,opus,gsm,ilbc,speex',
            'transport'  => 'nullable|string|max:50',
            'max_contacts'       => 'nullable|integer|min:1|max:10',
            'voicemail_enabled'  => 'boolean',
            'voicemail_email'    => 'nullable|email|max:255',
            'notes'      => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'extension.regex'  => "L'extension doit contenir uniquement des chiffres.",
            'extension.unique' => 'Cette extension est déjà utilisée.',
            'secret.min'       => 'Le mot de passe SIP doit faire au moins 8 caractères.',
        ];
    }
}
