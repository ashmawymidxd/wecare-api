<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('profile_image')->nullable();
            $table->string('name');
            $table->string('mobile');
            $table->string('email')->nullable();
            $table->string('nationality')->nullable();
            $table->string('preferred_language')->nullable();
            $table->text('address')->nullable();
            $table->date('joining_date')->nullable();
            $table->string('source_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('business_category')->nullable();
            $table->string('country')->nullable();
            $table->decimal('expected_contract_amount', 12, 2)->nullable();
            $table->decimal('expected_discount', 5, 2)->nullable();
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('source_id')->nullable()->constrained()->onDelete('set null');
             $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('inquiries');
    }
};
