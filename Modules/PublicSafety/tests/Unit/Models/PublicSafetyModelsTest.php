<?php

namespace Modules\PublicSafety\Tests\Unit\Models;

use Modules\PublicSafety\Models\Building;
use Modules\PublicSafety\Models\Campus;
use Modules\PublicSafety\Models\IncidentFile;
use Modules\PublicSafety\Models\IncidentReport;
use Modules\PublicSafety\Models\IncidentStatus;
use Modules\PublicSafety\Models\IncidentType;
use Modules\PublicSafety\Models\Menu;
use Modules\PublicSafety\Models\MenuRole;
use Modules\PublicSafety\Models\Message;
use Modules\PublicSafety\Models\MessageCategory;
use Modules\PublicSafety\Models\User;
use Modules\PublicSafety\Models\UserCampus;
use Modules\PublicSafety\Models\UserStatus;
use PHPUnit\Framework\TestCase;
use Tests\Support\AssertsModelConfiguration;

class PublicSafetyModelsTest extends TestCase
{
    use AssertsModelConfiguration;

    public function test_user_model_configuration(): void
    {
        $this->assertModelUsesConnection(User::class, 'pgsql');
        $this->assertModelFillable(User::class, [
            'name',
            'email',
            'password',
            'domain',
            'device_token',
            'user_status_id',
            'profile_picture',
        ]);
    }

    public function test_user_status_model_configuration(): void
    {
        $this->assertModelUsesConnection(UserStatus::class, 'pgsql');
        $this->assertModelUsesTable(UserStatus::class, 'user_statuses');
        $this->assertModelFillable(UserStatus::class, ['userStatuses']);
    }

    public function test_user_campus_model_configuration(): void
    {
        $this->assertModelUsesConnection(UserCampus::class, 'pgsql');
        $this->assertModelUsesTable(UserCampus::class, 'user_campuses');
        $this->assertModelFillable(UserCampus::class, [
            'user_id',
            'campus_id',
            'primary_campus',
        ]);
        $this->assertModelCastsInclude(UserCampus::class, [
            'primary_campus' => 'boolean',
        ]);
    }

    public function test_campus_model_configuration(): void
    {
        $this->assertModelUsesTable(Campus::class, 'campuses');
        $this->assertModelFillable(Campus::class, ['campus']);
    }

    public function test_building_model_configuration(): void
    {
        $this->assertModelUsesTable(Building::class, 'buildings');
        $this->assertModelFillable(Building::class, [
            'name',
            'location',
            'campus_id',
        ]);
    }

    public function test_incident_type_model_configuration(): void
    {
        $this->assertModelUsesTable(IncidentType::class, 'incident_types');
        $this->assertModelFillable(IncidentType::class, [
            'icon',
            'type',
            'message',
        ]);
    }

    public function test_incident_status_model_configuration(): void
    {
        $this->assertModelUsesTable(IncidentStatus::class, 'incident_statuses');
        $this->assertModelFillable(IncidentStatus::class, ['statuses']);
    }

    public function test_incident_report_model_configuration(): void
    {
        $this->assertModelUsesTable(IncidentReport::class, 'incident_reports');
        $this->assertModelFillable(IncidentReport::class, [
            'report',
            'description',
            'disposition',
            'case_number',
            'action',
            'location',
            'uploaded_by',
            'frequency',
            'incident_reoccured',
            'incident_status_id',
            'user_id',
            'campus_id',
            'building_id',
            'incident_type_id',
        ]);
        $this->assertModelCastsInclude(IncidentReport::class, [
            'incident_reoccured' => 'datetime',
        ]);
    }

    public function test_incident_file_model_configuration(): void
    {
        $this->assertModelUsesTable(IncidentFile::class, 'incident_files');
        $this->assertModelFillable(IncidentFile::class, [
            'incident_report_id',
            'path',
            'name',
        ]);
    }

    public function test_message_category_model_configuration(): void
    {
        $this->assertModelUsesTable(MessageCategory::class, 'message_categories');
        $this->assertModelFillable(MessageCategory::class, ['category']);
    }

    public function test_message_model_configuration(): void
    {
        $this->assertModelUsesTable(Message::class, 'messages');
        $this->assertModelFillable(Message::class, [
            'profile_pic',
            'sender',
            'message_category_id',
            'images',
            'message',
            'location',
            'date_sent',
            'is_deleted',
            'type',
        ]);
        $this->assertModelCastsInclude(Message::class, [
            'date_sent' => 'datetime',
            'is_deleted' => 'boolean',
        ]);
    }

    public function test_menu_model_configuration(): void
    {
        $this->assertModelUsesTable(Menu::class, 'menus');
        $this->assertModelFillable(Menu::class, [
            'name',
            'icon',
            'path',
        ]);
    }

    public function test_menu_role_model_configuration(): void
    {
        $this->assertModelUsesTable(MenuRole::class, 'menu_roles');
        $this->assertModelFillable(MenuRole::class, [
            'menu_id',
            'role_id',
        ]);
    }
}
