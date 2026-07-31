<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'porsql';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('budget_years')) {
            $schema->create('budget_years', function (Blueprint $table) {
                $table->id();
                $table->string('label', 20)->unique();
                $table->boolean('submissions_open')->default(false);
                $table->timestamps();
            });
        }

        if (!$schema->hasTable('budgets')) {
            $schema->create('budgets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cost_center_id')->constrained('cost_centers');
                $table->foreignId('budget_year_id')->constrained('budget_years');
                $table->foreignId('status_id')->constrained('statuses');
                $table->foreignId('stage_id')->constrained('stages');
                $table->unsignedInteger('current_stage_sequence')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();

                $table->unique(['cost_center_id', 'budget_year_id']);
            });
        }

        if (!$schema->hasTable('budget_line_items')) {
            $schema->create('budget_line_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
                $table->foreignId('chart_of_account_id')->constrained('chart_of_accounts');
                $table->decimal('amount', 15, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['budget_id', 'chart_of_account_id']);
            });
        }

        if (!$schema->hasTable('budget_approvals')) {
            $schema->create('budget_approvals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
                $table->uuid('user_id');
                $table->foreignId('stage_id')->constrained('stages');
                $table->string('status');
                $table->text('comments')->nullable();
                $table->timestamp('signed_at')->nullable();
                $table->timestamps();
            });
        }

        if (!$schema->hasTable('budget_logs')) {
            $schema->create('budget_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
                $table->uuid('user_id');
                $table->string('action');
                $table->text('summary')->nullable();
                $table->text('comments')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        $schema->dropIfExists('budget_logs');
        $schema->dropIfExists('budget_approvals');
        $schema->dropIfExists('budget_line_items');
        $schema->dropIfExists('budgets');
        $schema->dropIfExists('budget_years');
    }
};
