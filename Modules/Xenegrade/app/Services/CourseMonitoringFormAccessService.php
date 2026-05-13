<?php

namespace Modules\Xenegrade\Services;

/**
 * Reads boolean feature flags from `cmon_courseMonitoringSettings/global` that gate
 * access to course monitoring and annual forms. Missing keys default to enabled (true).
 */
class CourseMonitoringFormAccessService
{
    public const COLLECTION = 'cmon_courseMonitoringSettings';

    public const DOCUMENT_ID = 'global';

    /**
     * @param  array<string, mixed>|null  $doc
     * @return array{
     *     enableCourseMonitoringForm: bool,
     *     enableCourseCoordinatorForm: bool,
     *     enableProgramCoordinatorForm: bool,
     *     enableAnnualChairForm: bool,
     *     enableAnnualDeanForm: bool,
     *     enableAnnualVpForm: bool
     * }
     */
    public static function flagsFromDocument(?array $doc): array
    {
        $doc = $doc ?? [];

        return [
            'enableCourseMonitoringForm' => self::coerceBool($doc['enableCourseMonitoringForm'] ?? null, true),
            'enableCourseCoordinatorForm' => self::coerceBool($doc['enableCourseCoordinatorForm'] ?? null, true),
            'enableProgramCoordinatorForm' => self::coerceBool($doc['enableProgramCoordinatorForm'] ?? null, true),
            'enableAnnualChairForm' => self::coerceBool($doc['enableAnnualChairForm'] ?? null, true),
            'enableAnnualDeanForm' => self::coerceBool($doc['enableAnnualDeanForm'] ?? null, true),
            // Not in the original product list; defaults true so VP menu stays available unless explicitly disabled.
            'enableAnnualVpForm' => self::coerceBool(
                $doc['enableAnnualVpForm'] ?? $doc['enableAnnualVPForm'] ?? null,
                true
            ),
        ];
    }

    private static function coerceBool(mixed $value, bool $default): bool
    {
        if ($value === null) {
            return $default;
        }
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return ((int) $value) !== 0;
        }
        $s = strtolower(trim((string) $value));
        if ($s === '' || $s === 'null') {
            return $default;
        }
        if (in_array($s, ['0', 'false', 'no', 'off', 'disabled'], true)) {
            return false;
        }
        if (in_array($s, ['1', 'true', 'yes', 'on', 'enabled'], true)) {
            return true;
        }

        return $default;
    }
}
