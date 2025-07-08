<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('contract_number')->unique();
            $table->date('start_date');
            $table->date('expiry_date');
            $table->string('office_type');
            $table->string('city');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->decimal('contract_amount', 10, 2);
            $table->string('payment_method');
            $table->boolean('cheque_covered')->default(false);
            $table->decimal('cash_amount', 10, 2)->nullable();
            $table->string('cheque_number')->nullable();
            $table->date('due_date')->nullable();
            $table->string('discount_type')->nullable();
            $table->decimal('discount', 10, 2)->nullable();
            $table->decimal('electricity_fees', 10, 2)->nullable();
            $table->decimal('contract_ratification_fees', 10, 2)->nullable();
            $table->decimal('pro_amount_received', 10, 2)->nullable();
            $table->decimal('pro_expense', 10, 2)->nullable();
            $table->decimal('commission', 10, 2)->nullable();
            $table->decimal('actual_amount', 10, 2);
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->text('status')->default('New');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('contracts');
    }
};
