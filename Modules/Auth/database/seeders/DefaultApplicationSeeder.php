<?php

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\Menu;
use Modules\Auth\Models\User;

class DefaultApplicationSeeder extends Seeder
{
    public function run(): void
    {
        $applicationId = Menu::defaultApplicationId();

        if (!$applicationId) {
            return;
        }

        User::query()
            ->where(function ($query) use ($applicationId) {
                $query->whereNull('default_application_id')
                    ->orWhere('default_application_id', '!=', $applicationId);
            })
            ->update(['default_application_id' => $applicationId]);
    }
}
