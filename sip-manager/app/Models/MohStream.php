<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MohStream extends Model
{
    protected $fillable = [
        'name', 'display_name', 'url', 'enabled', 'created_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Asterisk MOH class name for this stream.
     */
    public function getMohClassName(): string
    {
        return 'stream-' . preg_replace('/[^a-z0-9-]/', '-', strtolower($this->name));
    }

    /**
     * Generate musiconhold.conf section for this stream.
     */
    public function toMohConf(): string
    {
        $class = $this->getMohClassName();
        $lines = [];
        $lines[] = "[{$class}]";
        $lines[] = "mode=custom";
        $lines[] = "application=/usr/bin/ffmpeg -i " . $this->url . " -f s16le -ar 8000 -ac 1 -loglevel quiet pipe:1";
        $lines[] = "format=slin";

        return implode("\n", $lines);
    }
}
