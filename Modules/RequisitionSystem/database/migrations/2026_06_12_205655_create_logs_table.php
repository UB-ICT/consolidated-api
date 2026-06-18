<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\RequisitionSystem\Database\Migrations\EnsuresTimestamps;
use Modules\RequisitionSystem\Support\RequisitionLogAction;

return new class extends Migration
{
    use EnsuresTimestamps;

    protected $connection = 'porsql';

    public function up(): void
    {
        $this->ensureTableWithTimestamps('logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisition_id')->constrained('requisitions')->onDelete('cascade');
            $table->uuid('user_id');
            $table->string('action')->default(RequisitionLogAction::UPDATED);
            $table->text('summary')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('public.users')
                ->onDelete('cascade');

            $table->index(['requisition_id', 'created_at']);
            $table->index('action');
        });

        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('logs')) {
            if (!$schema->hasColumn('logs', 'action')) {
                $schema->table('logs', function (Blueprint $table) {
                    $table->string('action')->default(RequisitionLogAction::UPDATED);
                });
            }

            if (!$schema->hasColumn('logs', 'summary')) {
                $schema->table('logs', function (Blueprint $table) {
                    $table->text('summary')->nullable();
                });
            }

            if ($schema->hasColumn('logs', 'comments')) {
                $schema->table('logs', function (Blueprint $table) {
                    $table->text('comments')->nullable()->change();
                });
            }

            if ($schema->hasColumn('logs', 'stage_id')) {
                $schema->table('logs', function (Blueprint $table) {
                    $table->dropForeign(['stage_id']);
                    $table->dropColumn('stage_id');
                });
            }
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('logs')) {
            return;
        }

        if (!$schema->hasColumn('logs', 'stage_id')) {
            $schema->table('logs', function (Blueprint $table) {
                $table->foreignId('stage_id')
                    ->nullable()
                    ->constrained('stages');
            });
        }

        if ($schema->hasColumn('logs', 'summary')) {
            $schema->table('logs', function (Blueprint $table) {
                $table->dropColumn('summary');
            });
        }

        if ($schema->hasColumn('logs', 'action')) {
            $schema->table('logs', function (Blueprint $table) {
                $table->dropColumn('action');
            });
        }
    }
};
