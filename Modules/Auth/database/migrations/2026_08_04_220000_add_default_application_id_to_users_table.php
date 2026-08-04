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

        if (!$schema->hasTable('users')) {
            return;
        }

        if (!$schema->hasColumn('users', 'default_application_id')) {
            $schema->table('users', function (Blueprint $table) {
                $table->uuid('default_application_id')->nullable()->after('google_id');
                $table->foreign('default_application_id')
                    ->references('id')
                    ->on('menus')
                    ->nullOnDelete();
            });
        }

        $requisitionAppId = DB::connection($this->connection)
            ->table('menus')
            ->whereNull('parent_id')
            ->where('type', 'application')
            ->where('path', '/requisitions')
            ->value('id');

        if ($requisitionAppId) {
            DB::connection($this->connection)
                ->table('users')
                ->whereNull('default_application_id')
                ->update(['default_application_id' => $requisitionAppId]);
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('users') || !$schema->hasColumn('users', 'default_application_id')) {
            return;
        }

        $schema->table('users', function (Blueprint $table) {
            $table->dropForeign(['default_application_id']);
            $table->dropColumn('default_application_id');
        });
    }
};
