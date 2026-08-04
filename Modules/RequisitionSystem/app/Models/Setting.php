<?php

namespace Modules\RequisitionSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $connection = 'porsql';

    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    public const GST_RATE_PERCENT_KEY = 'gst_rate_percent';

    public const DEFAULT_GST_RATE_PERCENT = 12.5;

    public static function getValue(string $key, ?string $default = null): ?string
    {
        $cached = Cache::remember(
            "requisition_setting:{$key}",
            now()->addMinutes(10),
            function () use ($key) {
                return static::query()->where('key', $key)->value('value');
            }
        );

        return $cached !== null ? (string) $cached : $default;
    }

    public static function gstRatePercent(): float
    {
        return (float) (static::getValue(
            self::GST_RATE_PERCENT_KEY,
            (string) self::DEFAULT_GST_RATE_PERCENT
        ) ?? self::DEFAULT_GST_RATE_PERCENT);
    }

    public static function forgetCached(string $key): void
    {
        Cache::forget("requisition_setting:{$key}");
    }
}
