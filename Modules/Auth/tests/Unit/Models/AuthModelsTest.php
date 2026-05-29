<?php

namespace Modules\Auth\Tests\Unit\Models;

use Modules\Auth\Models\Menu;
use Modules\Auth\Models\Permission;
use Modules\Auth\Models\PersonalAccessToken;
use Modules\Auth\Models\Role;
use Modules\Auth\Models\User;
use PHPUnit\Framework\TestCase;
use Tests\Support\AssertsModelConfiguration;

class AuthModelsTest extends TestCase
{
    use AssertsModelConfiguration;

    public function test_user_model_configuration(): void
    {
        $this->assertModelUsesConnection(User::class, 'pgsql');
        $this->assertModelFillable(User::class, [
            'name',
            'email',
            'guid',
            'domain',
            'password',
            'profile_picture',
            'status',
            'last_active',
            'device_token',
            'google_id',
            'email_verified_at',
        ]);
        $this->assertModelCastsInclude(User::class, [
            'email_verified_at' => 'datetime',
            'last_active' => 'datetime',
            'password' => 'hashed',
        ]);
    }

    public function test_role_model_configuration(): void
    {
        $this->assertModelUsesConnection(Role::class, 'pgsql');
        $this->assertModelFillable(Role::class, ['role_name', 'description']);
    }

    public function test_permission_model_configuration(): void
    {
        $this->assertModelUsesConnection(Permission::class, 'pgsql');
        $this->assertModelFillable(Permission::class, ['category', 'action_name']);
    }

    public function test_menu_model_configuration(): void
    {
        $this->assertModelUsesConnection(Menu::class, 'pgsql');
        $this->assertModelFillable(Menu::class, [
            'label',
            'path',
            'icon',
            'role_id',
            'parent_id',
            'sort_order',
        ]);
    }

    public function test_personal_access_token_model_configuration(): void
    {
        $this->assertModelUsesConnection(PersonalAccessToken::class, 'pgsql');
    }
}
