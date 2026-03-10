<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    use HasFactory;

    protected $table = 'app_settings';

    protected $fillable = [
        'key',
        'value',
        'type',
    ];

    /**
     * Get a setting value by key
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return self::castValue($setting->value, $setting->type);
    }

    /**
     * Set a setting value
     */
    public static function setValue(string $key, mixed $value, string $type = 'string'): self
    {
        $storedValue = $type === 'json' ? json_encode($value) : (string) $value;

        return self::updateOrCreate(
            ['key' => $key],
            ['value' => $storedValue, 'type' => $type]
        );
    }

    /**
     * Cast value to proper type
     */
    protected static function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Get company name
     */
    public static function getCompanyName(): string
    {
        return self::getValue('company_name', 'Sales Management');
    }

    /**
     * Get company phone
     */
    public static function getCompanyPhone(): string
    {
        return self::getValue('company_phone', '');
    }

    /**
     * Get company address
     */
    public static function getCompanyAddress(): string
    {
        return self::getValue('company_address', '');
    }

    /**
     * Get receipt footer
     */
    public static function getReceiptFooter(): string
    {
        return self::getValue('receipt_footer', 'Thank you for your patronage!');
    }
}
