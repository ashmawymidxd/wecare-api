<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralSettings extends Model
{
    use HasFactory;

    protected $fillable=[
        'language',
        'carrancy',
        'date_formate',
        'contract_duration',
        'renewal_remender',
        'tax_rates',
        'payment_alert'
    ];
}
