<?php

namespace Tests\Unit\Services;

use App\Services\CourseEvaluationAggregator;
use PHPUnit\Framework\TestCase;

class CourseEvaluationAggregatorTest extends TestCase
{
    public function test_merge_returns_empty_array_for_no_parts(): void
    {
        $this->assertSame([], CourseEvaluationAggregator::merge([]));
    }

    public function test_merge_returns_single_course_payload(): void
    {
        $parts = [
            [
                'label' => 'Section A',
                'course' => [
                    'title' => 'Intro',
                    'counts' => ['students' => 10],
                ],
            ],
        ];

        $result = CourseEvaluationAggregator::merge($parts);

        $this->assertSame('Intro', $result['title']);
        $this->assertSame(['students' => 10], $result['counts']);
    }

    public function test_merge_sums_numeric_scalars(): void
    {
        $parts = [
            ['label' => 'Section A', 'course' => ['total' => 5]],
            ['label' => 'Section B', 'course' => ['total' => 7]],
        ];

        $result = CourseEvaluationAggregator::merge($parts);

        $this->assertSame(12.0, $result['total']);
    }

    public function test_merge_prefers_non_empty_scalar_over_empty(): void
    {
        $parts = [
            ['label' => 'Section A', 'course' => ['notes' => '']],
            ['label' => 'Section B', 'course' => ['notes' => 'Needs follow-up']],
        ];

        $result = CourseEvaluationAggregator::merge($parts);

        $this->assertSame('Needs follow-up', $result['notes']);
    }

    public function test_merge_concatenates_conflicting_string_scalars_with_section_label(): void
    {
        $parts = [
            ['label' => 'Section A', 'course' => ['comment' => 'Good attendance']],
            ['label' => 'Section B', 'course' => ['comment' => 'Low participation']],
        ];

        $result = CourseEvaluationAggregator::merge($parts);

        $this->assertSame(
            "Good attendance\n\n— Section B —\n\nLow participation",
            $result['comment']
        );
    }

    public function test_merge_recursively_merges_nested_arrays(): void
    {
        $parts = [
            [
                'label' => 'Section A',
                'course' => [
                    'metrics' => ['completed' => 3, 'notes' => 'On track'],
                ],
            ],
            [
                'label' => 'Section B',
                'course' => [
                    'metrics' => ['completed' => 2, 'notes' => 'Delayed'],
                ],
            ],
        ];

        $result = CourseEvaluationAggregator::merge($parts);

        $this->assertSame(5.0, $result['metrics']['completed']);
        $this->assertSame(
            "On track\n\n— Section B —\n\nDelayed",
            $result['metrics']['notes']
        );
    }
}
