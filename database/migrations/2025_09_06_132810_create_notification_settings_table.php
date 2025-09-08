<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->boolean('contract_expiry')->default(true);
            $table->boolean('renewal_reminders')->default(true);
            $table->boolean('inspection')->default(true);
            $table->boolean('new_customer_added')->default(true);
            $table->boolean('commission_payment')->default(true);
            $table->boolean('archived_contracts')->default(true);
            $table->boolean('document_expiry_alerts')->default(true);
            $table->boolean('required_document_missing')->default(true);
            $table->timestamps();

            // Ensure each employee has only one notification settings record
            $table->unique('employee_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('notification_settings');
    }
}
