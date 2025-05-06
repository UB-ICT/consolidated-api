<?php

namespace Modules\PublicSafety\Database\Seeders;

use Modules\PublicSafety\Models\AccessRight;
use Illuminate\Database\Seeder;

class AccessRightSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        AccessRight::create(['description'=>'blah', 'role_id'=> 1]);
       

    }
}
