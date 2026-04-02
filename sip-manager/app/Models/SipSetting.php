<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SipSetting extends Model
{
    protected $table = 'sip_settings';

    protected $fillable = ['key', 'value', 'group', 'type'];

    public function getTypedValue(): mixed
    {
        return match ($this->type) {
            'int'  => (int) $this->value,
            'bool' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("sip_setting:{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->getTypedValue() : $default;
        });
    }

    public static function set(string $key, mixed $value, string $group = 'general', string $type = 'string'): void
    {
        $storeValue = is_array($value) ? json_encode($value) : (string) $value;

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $storeValue, 'group' => $group, 'type' => $type]
        );

        Cache::forget("sip_setting:{$key}");
    }
}
