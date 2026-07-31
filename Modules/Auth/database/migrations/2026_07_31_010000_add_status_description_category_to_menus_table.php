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
        if (!Schema::connection($this->connection)->hasTable('menus')) {
            return;
        }

        Schema::connection($this->connection)->table('menus', function (Blueprint $table) {
            if (!Schema::connection($this->connection)->hasColumn('menus', 'status')) {
                $table->string('status')->default('active')->after('icon');
            }

            if (!Schema::connection($this->connection)->hasColumn('menus', 'description')) {
                $table->string('description')->nullable()->after('status');
            }

            if (!Schema::connection($this->connection)->hasColumn('menus', 'category')) {
                $table->string('category')->nullable()->after('description');
            }
        });

        DB::connection($this->connection)
            ->table('menus')
            ->whereNull('status')
            ->update(['status' => 'active']);
    }

    public function down(): void
    {
        if (!Schema::connection($this->connection)->hasTable('menus')) {
            return;
        }

        Schema::connection($this->connection)->table('menus', function (Blueprint $table) {
            if (Schema::connection($this->connection)->hasColumn('menus', 'category')) {
                $table->dropColumn('category');
            }

            if (Schema::connection($this->connection)->hasColumn('menus', 'description')) {
                $table->dropColumn('description');
            }

            if (Schema::connection($this->connection)->hasColumn('menus', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
