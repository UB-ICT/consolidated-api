<?php

namespace Modules\Xenegrade\Tests\Unit\Services;

use Modules\Xenegrade\Services\CourseMonitoringFormAccessService;
use PHPUnit\Framework\TestCase;

class CourseMonitoringFormAccessServiceTest extends TestCase
{
    public function test_collection_and_document_constants_are_defined(): void
    {
        $this->assertSame('cmon_courseMonitoringSettings', CourseMonitoringFormAccessService::COLLECTION);
        $this->assertSame('global', CourseMonitoringFormAccessService::DOCUMENT_ID);
    }

    public function test_flags_from_document_defaults_to_enabled_when_document_is_null(): void
    {
        $flags = CourseMonitoringFormAccessService::flagsFromDocument(null);

        $this->assertTrue($flags['enableCourseMonitoringForm']);
        $this->assertTrue($flags['enableCourseCoordinatorForm']);
        $this->assertTrue($flags['enableProgramCoordinatorForm']);
        $this->assertTrue($flags['enableAnnualChairForm']);
        $this->assertTrue($flags['enableAnnualDeanForm']);
        $this->assertTrue($flags['enableAnnualVpForm']);
    }

    public function test_flags_from_document_respects_explicit_boolean_values(): void
    {
        $flags = CourseMonitoringFormAccessService::flagsFromDocument([
            'enableCourseMonitoringForm' => false,
            'enableCourseCoordinatorForm' => true,
            'enableProgramCoordinatorForm' => 'off',
            'enableAnnualChairForm' => 0,
            'enableAnnualDeanForm' => 'yes',
            'enableAnnualVpForm' => 'disabled',
        ]);

        $this->assertFalse($flags['enableCourseMonitoringForm']);
        $this->assertTrue($flags['enableCourseCoordinatorForm']);
        $this->assertFalse($flags['enableProgramCoordinatorForm']);
        $this->assertFalse($flags['enableAnnualChairForm']);
        $this->assertTrue($flags['enableAnnualDeanForm']);
        $this->assertFalse($flags['enableAnnualVpForm']);
    }

    public function test_flags_from_document_accepts_enable_annual_vp_form_alias(): void
    {
        $flags = CourseMonitoringFormAccessService::flagsFromDocument([
            'enableAnnualVPForm' => false,
        ]);

        $this->assertFalse($flags['enableAnnualVpForm']);
    }

    public function test_flags_from_document_uses_default_for_unknown_string_values(): void
    {
        $flags = CourseMonitoringFormAccessService::flagsFromDocument([
            'enableCourseMonitoringForm' => 'maybe',
        ]);

        $this->assertTrue($flags['enableCourseMonitoringForm']);
    }
}
