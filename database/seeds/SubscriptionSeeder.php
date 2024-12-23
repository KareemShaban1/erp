<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Superadmin\Entities\Subscription;

class SubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //

        Subscription::create([
            'name' => 'Basic',
            'business_id'=>3,
            'price' => 10,

        ]);
    }
}
