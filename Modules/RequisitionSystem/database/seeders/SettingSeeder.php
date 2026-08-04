<?php

namespace Modules\RequisitionSystem\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\RequisitionSystem\Models\Setting;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        // Synced from live settings.
        Setting::query()->updateOrCreate(
            ['key' => 'gst_rate_percent'],
            [
                'value' => '12.5',
                'description' => 'General Sales Tax rate applied to GST-applicable requisition line items (percent).',
            ]
        );
    }
}
