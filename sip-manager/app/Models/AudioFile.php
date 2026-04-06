<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AudioFile extends Model
{
    protected $fillable = [
        'name', 'original_name', 'filename', 'category',
        'moh_class', 'duration', 'file_size', 'format', 'created_by',
    ];

    protected $casts = [
        'duration'  => 'integer',
        'file_size' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Full path to the converted WAV file on disk.
     */
    public function getFilePath(): string
    {
        return $this->category === 'moh'
            ? "/var/lib/asterisk/moh/{$this->filename}"
            : "/var/lib/asterisk/sounds/custom/{$this->filename}";
    }

    /**
     * Asterisk-compatible reference (without extension) for Playback().
     */
    public function getAsteriskRef(): string
    {
        return 'custom/' . pathinfo($this->filename, PATHINFO_FILENAME);
    }
}
