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
            $table->string('language')->default('En');
            $table->string('carrancy')->default('ADM');
            $table->string('date_formate')->default('MM/DD/WW');
            $table->integer('contract_duration')->default(1);
            $table->integer('renewal_remender')->default(30);
            $table->integer('tax_rates')->default(15);
            $table->integer('payment_alert')->default(30);
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
