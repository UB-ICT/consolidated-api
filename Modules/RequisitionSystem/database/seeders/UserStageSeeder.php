<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\User;
use Modules\RequisitionSystem\Models\Stage;
use Modules\RequisitionSystem\Models\UserStage;

class UserStageSeeder extends Seeder
{
    /**
     * Synced from live user_stages (email → stage name).
     * Includes Draft and standalone Purchase Approval (not on the operations pipeline).
     *
     * @var list<array{email: string, stage: string}>
     */
    private const ASSIGNMENTS = [
        ['email' => 'ylin@ub.edu.bz', 'stage' => 'Budget Officer'],
        ['email' => 'lramirez@ub.edu.bz', 'stage' => 'Budget Officer Review'],
        ['email' => 'ylin@ub.edu.bz', 'stage' => 'Budget Officer Review'],
        ['email' => 'fburns@ub.edu.bz', 'stage' => "Director's Approval"],
        ['email' => 'luis.herrera@ub.edu.bz', 'stage' => "Director's Approval"],
        ['email' => 'npolanco@ub.edu.bz', 'stage' => 'Draft'],
        ['email' => 'igarcia@ub.edu.bz', 'stage' => 'Finance Approval'],
        ['email' => 'igarcia@ub.edu.bz', 'stage' => 'Finance Director Approval'],
        ['email' => 'ccocom@ub.edu.bz', 'stage' => 'Purchase Approval'],
        ['email' => 'jose.lopez@ub.edu.bz', 'stage' => 'Purchase Approval'],
        ['email' => 'msimon@ub.edu.bz', 'stage' => 'VP Approval'],
    ];

    public function run(): void
    {
        foreach (self::ASSIGNMENTS as $row) {
            $user = User::where('email', $row['email'])->first();
            $stage = Stage::firstOrCreate(['name' => $row['stage']]);

            if (!$user) {
                $this->command?->warn("Skipping user_stage for missing user {$row['email']}.");
                continue;
            }

            UserStage::firstOrCreate([
                'user_id' => $user->id,
                'stage_id' => $stage->id,
            ]);
        }
    }
}
