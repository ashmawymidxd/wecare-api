<?php
// app/Models/GeneralSetting.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'language',
        'currency',
        'date_format',
        'default_contract_duration',
        'renewal_reminder',
        'tax_rate',
        'late_payment_alert',
        'grace_period',
        'late_payment_fee',
        'maximum_commission',
        'maximum_sale'
    ];

    protected $casts = [
        'late_payment_alert' => 'boolean',
        'tax_rate' => 'decimal:2',
        'late_payment_fee' => 'decimal:2',
        'maximum_commission' => 'decimal:2',
        'maximum_sale' => 'decimal:2'
    ];
}
