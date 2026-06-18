<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\RequisitionSystem\Database\Migrations\EnsuresTimestamps;

return new class extends Migration
{
    use EnsuresTimestamps;

    protected $connection = 'porsql';

    public function up(): void
    {
        $this->ensureTableWithTimestamps('requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('cost_center_id')->constrained('cost_centers');
            $table->timestamp('date_prepared')->useCurrent();
            $table->foreignId('status_id')->constrained('statuses');
            $table->foreignId('currency_id')->constrained('currencies');
            $table->decimal('total', 15, 2);
            $table->foreignId('stage_id')->constrained('stages');
            $table->string('priority')->default('routine');
            $table->date('expected_delivery_date')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->date('reminder_date')->nullable();
            $table->timestamps();
        });

        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('requisitions') && $schema->hasColumn('requisitions', 'supplier_id')) {
            $schema->table('requisitions', function (Blueprint $table) {
                $table->dropForeign(['supplier_id']);
                $table->dropColumn('supplier_id');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        foreach ([
            'logs',
            'requisition_suppliers',
            'items',
            'attachments',
            'approvals',
        ] as $dependentTable) {
            $schema->dropIfExists($dependentTable);
        }

        $schema->dropIfExists('requisitions');
    }
};
