<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('general_settings', function (Blueprint $table) {
            $table->id();
            $table->string('language')->default('en');
            $table->string('currency')->default('USD');
            $table->string('date_format')->default('Y-m-d');
            $table->integer('default_contract_duration')->default(12); // in months
            $table->integer('renewal_reminder')->default(30); // days before renewal
            $table->decimal('tax_rate', 5, 2)->default(0.00);
            $table->boolean('late_payment_alert')->default(true);
            $table->integer('grace_period')->default(7); // days
            $table->decimal('late_payment_fee', 10, 2)->default(0.00);
            $table->decimal('maximum_commission', 10, 2)->default(0.00);
            $table->decimal('maximum_sale', 10, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_settings');
    }
};
