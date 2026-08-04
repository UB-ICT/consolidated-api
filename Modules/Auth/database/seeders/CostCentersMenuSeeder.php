<?php

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Auth\Models\Menu;
use Modules\Auth\Models\Role;

/**
 * Ensures the Requisition System "Cost Centers" submenu exists and is
 * assigned to all requisition roles via role_menu.
 */
class CostCentersMenuSeeder extends Seeder
{
    public function run(): void
    {
        $roleNames = [
            'super-admin',
            'budget-officer',
            'president',
            'vice-president',
            'director-of-finance',
            'accounts-payable',
            'senior-account',
            'director-dean',
            'purchase-officer',
            'requester',
        ];

        $roleIds = Role::query()
            ->whereIn('role_name', $roleNames)
            ->pluck('id')
            ->all();

        if ($roleIds === []) {
            return;
        }

        $parentId = Menu::query()
            ->where('path', '/requisitions')
            ->where('type', Menu::TYPE_APPLICATION)
            ->whereNull('parent_id')
            ->value('id');

        if (!$parentId) {
            return;
        }

        $menu = Menu::firstOrNew([
            'path' => '/requisitions/cost-centers',
            'parent_id' => $parentId,
            'type' => Menu::TYPE_SUBMENU,
        ]);

        if (!$menu->exists) {
            $menu->id = (string) Str::uuid();
        }

        $menu->fill([
            'label' => 'Cost Centers',
            'icon' => 'building-2',
            'sort_order' => 5,
            'status' => Menu::STATUS_ACTIVE,
        ])->save();

        $menu->roles()->sync($roleIds);

        Menu::query()
            ->where('parent_id', $parentId)
            ->where('path', '/requisitions/budgets')
            ->update(['sort_order' => 6]);

        Menu::query()
            ->where('parent_id', $parentId)
            ->where('path', '/requisitions/pipelines')
            ->update(['sort_order' => 7]);
    }
}
