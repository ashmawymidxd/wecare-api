<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'contract_number',
        'start_date',
        'expiry_date',
        'office_type',
        'city',
        'branch_id',
        'number_of_desks',
        'contract_amount',
        'payment_method',
        'cheque_covered',
        'cash_amount',
        'cheque_number',
        'due_date',
        'discount_type',
        'discount',
        'electricity_fees',
        'contract_ratification_fees',
        'pro_amount_received',
        'pro_expense',
        'commission',
        'actual_amount',
        'payment_date',
        'notes'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function attachments()
    {
        return $this->hasMany(ContractAttachment::class);
    }
}
