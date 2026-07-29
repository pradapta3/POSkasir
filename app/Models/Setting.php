<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Lightweight key-value store for store-wide configuration (currently just
 * the default tax rate; add more keys here as the settings screen grows
 * rather than adding new columns to unrelated tables).
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
