<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    /** @use HasFactory<\Database\Factories\SettingFactory> */
    use HasFactory;

    protected static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
        'is_public',
    ];

    protected $appends = ['text_value', 'number_value', 'json_value', 'boolean_value'];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function getValueAttribute($value)
    {
        if ($this->type === 'json') {
            return json_decode($value, true) ?? [];
        }

        if ($this->type === 'boolean') {
            return $value === '1';
        }

        return $value;
    }

    public function getTextValueAttribute()
    {
        return $this->attributes['value'] ?? '';
    }

    public function setTextValueAttribute($value)
    {
        $this->attributes['value'] = $value;
    }

    public function getNumberValueAttribute()
    {
        return $this->attributes['value'] ?? '0';
    }

    public function setNumberValueAttribute($value)
    {
        $this->attributes['value'] = $value;
    }

    public function getJsonValueAttribute()
    {
        return $this->attributes['value'] ?? '{}';
    }

    public function setJsonValueAttribute($value)
    {
        $this->attributes['value'] = $value;
    }

    public function getBooleanValueAttribute()
    {
        if (! $this->exists && ! isset($this->attributes['value'])) {
            return false;
        }

        return ($this->attributes['value'] ?? '0') === '1';
    }

    public function setBooleanValueAttribute($value)
    {
        $this->attributes['value'] = $value ? '1' : '0';
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        return $setting?->value ?? $default;
    }

    public static function setValue(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
