<?php

namespace App\Services;

/**
 * Merge multiple per-section course evaluation payloads into one course-level object.
 */
class CourseEvaluationAggregator
{
    public const AGGREGATE_SECTION_ID = '__AGG__';

    /**
     * @param  list<array{label: string, course: array<string, mixed>}>  $parts
     * @return array<string, mixed>
     */
    public static function merge(array $parts): array
    {
        if ($parts === []) {
            return [];
        }

        $base = self::deepClone($parts[0]['course']);
        for ($i = 1; $i < count($parts); $i++) {
            $base = self::mergeTwo($base, $parts[$i]['course'], $parts[$i]['label']);
        }

        return $base;
    }

    /**
     * @param  array<string, mixed>  $a
     * @param  array<string, mixed>  $b
     * @return array<string, mixed>
     */
    private static function mergeTwo(array $a, array $b, string $sectionLabel): array
    {
        $keys = array_unique(array_merge(array_keys($a), array_keys($b)));
        $out = [];
        foreach ($keys as $k) {
            $va = $a[$k] ?? null;
            $vb = $b[$k] ?? null;
            if (! array_key_exists($k, $a)) {
                $out[$k] = $vb;
                continue;
            }
            if (! array_key_exists($k, $b)) {
                $out[$k] = $va;
                continue;
            }
            if (is_array($va) && is_array($vb)) {
                $out[$k] = self::mergeTwo($va, $vb, $sectionLabel);
                continue;
            }
            $out[$k] = self::mergeScalars($va, $vb, $sectionLabel);
        }

        return $out;
    }

    private static function mergeScalars($va, $vb, string $sectionLabel)
    {
        if (($va === null || $va === '') && $vb !== null && $vb !== '') {
            return $vb;
        }
        if (($vb === null || $vb === '') && $va !== null && $va !== '') {
            return $va;
        }
        if ($va === $vb) {
            return $va;
        }
        $sa = is_string($va) ? trim($va) : (string) $va;
        $sb = is_string($vb) ? trim($vb) : (string) $vb;
        if ($sa === '' || $sb === '') {
            return $sa !== '' ? $sa : $sb;
        }
        if (is_numeric($sa) && is_numeric($sb)) {
            return (float) $sa + (float) $sb;
        }

        return $sa . "\n\n— " . $sectionLabel . " —\n\n" . $sb;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function deepClone(array $data): array
    {
        return json_decode(json_encode($data), true) ?? [];
    }
}
