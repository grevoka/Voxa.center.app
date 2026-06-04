<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    protected $fillable = [
        'prenom', 'nom', 'telephone', 'phone_normalized', 'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Digits-only phone form used for deduplication. Collapses common French
     * formats (0033xx, +33xx, 33xx) to the national leading-zero form.
     */
    public static function normalizePhone(?string $raw): string
    {
        $d = preg_replace('/\D/', '', (string) $raw);
        if (str_starts_with($d, '0033') && strlen($d) === 13) return '0' . substr($d, 4);
        if (str_starts_with($d, '33') && strlen($d) === 11)   return '0' . substr($d, 2);
        return $d;
    }
}
