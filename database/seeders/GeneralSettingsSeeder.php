<?php
// database/seeders/GeneralSettingsSeeder.php

namespace Database\Seeders;

use App\Models\GeneralSetting;
use Illuminate\Database\Seeder;

class GeneralSettingsSeeder extends Seeder
{
    public function run()
    {
        GeneralSetting::create([
            'language' => 'en',
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
            'default_contract_duration' => 12,
            'renewal_reminder' => 30,
            'tax_rate' => 7.5,
            'late_payment_alert' => true,
            'grace_period' => 7,
            'late_payment_fee' => 25.00,
            'maximum_commission' => 1000.00,
            'maximum_sale' => 10000.00,
        ]);
    }
}
