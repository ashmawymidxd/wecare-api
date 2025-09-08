<?php
// app/Models/NotificationSetting.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'contract_expiry',
        'renewal_reminders',
        'inspection',
        'new_customer_added',
        'commission_payment',
        'archived_contracts',
        'document_expiry_alerts',
        'required_document_missing'
    ];

    protected $casts = [
        'contract_expiry' => 'boolean',
        'renewal_reminders' => 'boolean',
        'inspection' => 'boolean',
        'new_customer_added' => 'boolean',
        'commission_payment' => 'boolean',
        'archived_contracts' => 'boolean',
        'document_expiry_alerts' => 'boolean',
        'required_document_missing' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
