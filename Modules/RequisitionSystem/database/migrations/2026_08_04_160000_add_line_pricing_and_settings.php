<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'porsql';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('settings')) {
            $schema->create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        DB::connection($this->connection)->table('settings')->updateOrInsert(
            ['key' => 'gst_rate_percent'],
            [
                'value' => '12.5',
                'description' => 'General Sales Tax rate applied to GST-applicable requisition line items (percent).',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        if ($schema->hasTable('items')) {
            $schema->table('items', function (Blueprint $table) use ($schema) {
                if (!$schema->hasColumn('items', 'subtotal')) {
                    $table->decimal('subtotal', 15, 2)->default(0)->after('unit_cost');
                }
                if (!$schema->hasColumn('items', 'discount_amount')) {
                    $table->decimal('discount_amount', 15, 2)->default(0)->after('subtotal');
                }
                if (!$schema->hasColumn('items', 'gst_applicable')) {
                    $table->boolean('gst_applicable')->default(false)->after('discount_amount');
                }
                if (!$schema->hasColumn('items', 'gst_amount')) {
                    $table->decimal('gst_amount', 15, 2)->default(0)->after('gst_applicable');
                }
            });

            // Backfill subtotal from existing quantity * unit_cost; total stays as-is until re-saved.
            DB::connection($this->connection)->statement(
                'UPDATE items SET subtotal = ROUND(quantity * unit_cost, 2) WHERE subtotal = 0'
            );
        }

        if ($schema->hasTable('requisitions')) {
            $schema->table('requisitions', function (Blueprint $table) use ($schema) {
                if (!$schema->hasColumn('requisitions', 'discount_type')) {
                    $table->string('discount_type')->default('none')->after('total');
                }
                if (!$schema->hasColumn('requisitions', 'discount_value')) {
                    $table->decimal('discount_value', 15, 2)->default(0)->after('discount_type');
                }
                if (!$schema->hasColumn('requisitions', 'discount_amount')) {
                    $table->decimal('discount_amount', 15, 2)->default(0)->after('discount_value');
                }
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('requisitions')) {
            $schema->table('requisitions', function (Blueprint $table) use ($schema) {
                foreach (['discount_amount', 'discount_value', 'discount_type'] as $column) {
                    if ($schema->hasColumn('requisitions', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if ($schema->hasTable('items')) {
            $schema->table('items', function (Blueprint $table) use ($schema) {
                foreach (['gst_amount', 'gst_applicable', 'discount_amount', 'subtotal'] as $column) {
                    if ($schema->hasColumn('items', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        $schema->dropIfExists('settings');
    }
};
