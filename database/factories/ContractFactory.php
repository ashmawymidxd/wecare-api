<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class ContractFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // Generate start date (past 1 year to future 1 month)
        $startDate = Carbon::today()
            ->subMonths(12) // 1 year ago
            ->addDays(rand(0, 365 + 30)); // Random up to 13 months ahead

        // Generate expiry date (6-24 months after start date)
        $expiryDate = (clone $startDate)->addMonths(rand(6, 24));

        $contractAmount = $this->faker->randomFloat(2, 1000, 10000);
        $paymentMethod = $this->faker->randomElement(['Cash', 'Cheque', 'Bank Transfer', 'Credit Card']);
        $discountType = $this->faker->randomElement(['Percentage', 'Fixed', null]);

        // Safely generate payment date (between start date and min(expiry, today))
        $paymentEndDate = min($expiryDate, Carbon::today());
        $paymentDate = $startDate->lt($paymentEndDate)
            ? $this->faker->optional(0.7)->dateTimeBetween(
                $startDate->format('Y-m-d'),
                $paymentEndDate->format('Y-m-d')
            )
            : null;

        // Safely generate due date for cheques
        $dueDate = null;
        if ($paymentMethod === 'Cheque') {
            $dueDate = $this->faker->dateTimeBetween(
                $startDate->format('Y-m-d'),
                $expiryDate->format('Y-m-d')
            )->format('Y-m-d');
        }

        return [
            'customer_id' => Customer::factory(),
            'contract_number' => 'CNTR-' . $this->faker->unique()->numberBetween(1000, 9999),
            'start_date' => $startDate->format('Y-m-d'),
            'expiry_date' => $expiryDate->format('Y-m-d'),
            'office_type' => $this->faker->randomElement(['Private', 'Shared', 'Open Space', 'Meeting Room']),
            'city' => $this->faker->city(),
            'branch_id' => Branch::factory(),
            'number_of_desks' => $this->faker->numberBetween(1, 10),
            'contract_amount' => $contractAmount,
            'payment_method' => $paymentMethod,
            'cheque_covered' => $paymentMethod === 'Cheque' ? $this->faker->boolean() : false,
            'cash_amount' => $paymentMethod === 'Cash' ? $contractAmount : null,
            'cheque_number' => $paymentMethod === 'Cheque' ? 'CHQ-' . $this->faker->numberBetween(1000, 9999) : null,
            'due_date' => $dueDate,
            'discount_type' => $discountType,
            'discount' => $discountType ? $this->faker->randomFloat(2, 0, $discountType === 'Percentage' ? 20 : 500) : null,
            'electricity_fees' => $this->faker->randomFloat(2, 0, 200),
            'contract_ratification_fees' => $this->faker->randomFloat(2, 0, 500),
            'pro_amount_received' => $this->faker->randomFloat(2, 0, $contractAmount),
            'pro_expense' => $this->faker->randomFloat(2, 0, 1000),
            'commission' => $this->faker->randomFloat(2, 0, 500),
            'actual_amount' => $contractAmount,
            'payment_date' => $paymentDate ? $paymentDate->format('Y-m-d') : null,
            'notes' => $this->faker->optional(0.3)->sentence(),
        ];
    }
}
