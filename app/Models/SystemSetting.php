<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        return cache()->remember("system-setting:{$key}", 60, fn () => static::query()->where('key', $key)->value('value') ?? $default);
    }

    public static function putValue(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => (string) $value]);
        cache()->forget("system-setting:{$key}");
    }
}
