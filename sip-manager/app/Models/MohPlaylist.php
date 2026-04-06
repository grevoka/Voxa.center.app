<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MohPlaylist extends Model
{
    protected $fillable = [
        'name', 'display_name', 'files', 'enabled', 'created_by',
    ];

    protected $casts = [
        'files' => 'array',
        'enabled' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getMohClassName(): string
    {
        return 'playlist-' . preg_replace('/[^a-z0-9-]/', '-', strtolower($this->name));
    }

    public function toMohConf(string $mohDir): string
    {
        $class = $this->getMohClassName();
        $lines = [];
        $lines[] = "[{$class}]";
        $lines[] = "mode=playlist";
        $lines[] = "sort=alpha";

        foreach ($this->files ?? [] as $file) {
            $lines[] = "entry={$mohDir}/{$file}";
        }

        return implode("\n", $lines);
    }
}
