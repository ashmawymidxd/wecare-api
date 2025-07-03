<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\Customer;
use App\Models\Branch;
use Illuminate\Database\Seeder;
use Nette\Utils\Random;

class ContractSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create 30 random contracts
        Contract::factory()->count(30)->create();

        // Create some specific contract examples
        $premiumCustomer = Customer::first();
        $mainBranch = Branch::first();

        Contract::create([
            'customer_id' => $premiumCustomer->id,
            'contract_number' => 'CNTR-VIP-001',
            'start_date' => now(),
            'expiry_date' => now()->addYears(2),
            'office_type' => 'Private',
            'city' => 'New York',
            'branch_id' => $mainBranch->id,
            'number_of_desks' => 5,
            'contract_amount' => 15000.00,
            'payment_method' => 'Bank Transfer',
            'cheque_covered' => false,
            'discount_type' => 'Percentage',
            'discount' => 10.00,
            'electricity_fees' => 150.00,
            'contract_ratification_fees' => 500.00,
            'pro_amount_received' => 5000.00,
            'pro_expense' => 200.00,
            'commission' => 750.00,
            'actual_amount' => 13500.00,
            'payment_date' => now(),
            'notes' => 'Premium customer with special discount',
            'status'=> 'active'
        ]);
    }
}
