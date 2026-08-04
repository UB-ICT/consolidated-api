<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('role_menu')) {
            $schema->create('role_menu', function (Blueprint $table) {
                $table->foreignUuid('role_id')->constrained('roles')->onDelete('cascade');
                $table->foreignUuid('menu_id')->constrained('menus')->onDelete('cascade');
                $table->primary(['role_id', 'menu_id']);
            });
        }

        if (!$schema->hasTable('menus') || !$schema->hasColumn('menus', 'role_id')) {
            return;
        }

        $menus = DB::connection($this->connection)
            ->table('menus')
            ->whereNotNull('role_id')
            ->orderBy('created_at')
            ->get();

        $groups = [];

        foreach ($menus as $menu) {
            $key = ($menu->parent_id ?? 'null').'|'.$menu->path.'|'.$menu->type;
            $groups[$key][] = $menu;
        }

        foreach ($groups as $group) {
            $canonical = $group[0];
            $roleIds = collect($group)->pluck('role_id')->unique()->filter()->values();
            $duplicateIds = collect($group)
                ->pluck('id')
                ->reject(fn ($id) => $id === $canonical->id)
                ->values();

            foreach ($roleIds as $roleId) {
                DB::connection($this->connection)->table('role_menu')->insertOrIgnore([
                    'role_id' => $roleId,
                    'menu_id' => $canonical->id,
                ]);
            }

            if ($duplicateIds->isNotEmpty()) {
                DB::connection($this->connection)
                    ->table('menus')
                    ->whereIn('parent_id', $duplicateIds)
                    ->update(['parent_id' => $canonical->id]);

                DB::connection($this->connection)
                    ->table('menus')
                    ->whereIn('id', $duplicateIds)
                    ->delete();
            }
        }

        $schema->table('menus', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('menus') && !$schema->hasColumn('menus', 'role_id')) {
            $schema->table('menus', function (Blueprint $table) {
                $table->foreignUuid('role_id')
                    ->nullable()
                    ->constrained('roles')
                    ->onDelete('set null');
            });

            $pivots = DB::connection($this->connection)->table('role_menu')->get();

            foreach ($pivots as $pivot) {
                DB::connection($this->connection)
                    ->table('menus')
                    ->where('id', $pivot->menu_id)
                    ->whereNull('role_id')
                    ->update(['role_id' => $pivot->role_id]);
            }
        }

        $schema->dropIfExists('role_menu');
    }
};
