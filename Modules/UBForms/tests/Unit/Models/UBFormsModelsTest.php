<?php

namespace Modules\UBForms\Tests\Unit\Models;

use Modules\UBForms\Models\Faculty;
use Modules\UBForms\Models\Finance;
use Modules\UBForms\Models\HumanResources;
use Modules\UBForms\Models\Menu;
use Modules\UBForms\Models\PersonalAccessToken;
use Modules\UBForms\Models\Records;
use Modules\UBForms\Models\Staff;
use Modules\UBForms\Models\User;
use PHPUnit\Framework\TestCase;
use Tests\Support\AssertsModelConfiguration;

class UBFormsModelsTest extends TestCase
{
    use AssertsModelConfiguration;

    public function test_user_model_configuration(): void
    {
        $this->assertModelUsesConnection(User::class, 'pgsql');
        $this->assertModelFillable(User::class, [
            'name',
            'email',
            'password',
        ]);
    }

    public function test_staff_model_configuration(): void
    {
        $this->assertModelUsesConnection(Staff::class, 'firestore');
        $this->assertModelFillable(Staff::class, [
            'email',
            'userID',
            'name',
            'academicYearID',
            'department',
            'reportsTo',
            'deadline',
            'missionStatement',
            'strategicGoals',
            'accomplishments',
            'researchPartnerships',
            'studentSuccess',
            'activities',
            'administrativeData',
            'financialBudget',
            'meetings',
            'formSubmitted',
            'otherComments',
        ]);
    }

    public function test_faculty_model_configuration(): void
    {
        $this->assertModelUsesConnection(Faculty::class, 'mongodb');
        $this->assertModelFillable(Faculty::class, [
            'email',
            'userID',
            'academicYearID',
            'faculty',
            'units',
            'deadline',
            'departmentList',
            'missionStatement',
            'strategicGoals',
            'accomplishments',
            'researchPartnerships',
            'revisedAcademics',
            'academicPrograms',
            'courses',
            'eliminatedAcademicPrograms',
            'retentionOfStudents',
            'studentInternships',
            'degreesConferred',
            'studentSuccess',
            'activities',
            'administrativeData',
            'financialBudget',
            'meetings',
            'formSubmitted',
            'otherComments',
        ]);
    }

    public function test_records_model_configuration(): void
    {
        $this->assertModelUsesConnection(Records::class, 'firestore');
        $this->assertModelFillable(Records::class, [
            'email',
            'name',
            'userID',
            'academicYearID',
            'department',
            'reportsTo',
            'deadline',
            'currentStudentEnrollmentTrend',
            'studentEnrollmentTrend',
            'enrollmentTrendPerFaculty',
            'graduationStatistics',
            'studentOrigin',
            'campusStatistics',
            'graduates',
            'formSubmitted',
        ]);
    }

    public function test_human_resources_model_configuration(): void
    {
        $this->assertModelUsesConnection(HumanResources::class, 'mongodb');
        $this->assertModelFillable(HumanResources::class, [
            'email',
            'userID',
            'academicYearID',
            'department',
            'reportsTo',
            'deadline',
            'numberOfStaff',
            'formSubmitted',
        ]);
    }

    public function test_finance_model_configuration(): void
    {
        $this->assertModelUsesConnection(Finance::class, 'mongodb');
        $this->assertModelFillable(Finance::class, [
            'email',
            'userID',
            'email',
            'academicYearID',
            'department',
            'deadline',
            'income',
            'expenditure',
            'investments',
            'formSubmitted',
        ]);
    }

    public function test_menu_model_configuration(): void
    {
        $this->assertModelUsesConnection(Menu::class, 'firestore');
        $this->assertModelFillable(Menu::class, [
            'name',
            'path',
            'icon',
            'order',
            'is_active',
        ]);
    }

    public function test_personal_access_token_model_configuration(): void
    {
        $this->assertModelFillable(PersonalAccessToken::class, [
            'name',
            'token',
            'abilities',
            'expires_at',
        ]);
        $this->assertModelCastsInclude(PersonalAccessToken::class, [
            'abilities' => 'json',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ]);
    }
}
