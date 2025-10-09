<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'profile_image',
        'name',
        'mobile',
        'email',
        'nationality',
        'preferred_language',
        'address',
        'joining_date',
        'source_name',
        'company_name',
        'business_category',
        'country',
        'expected_contract_amount',
        'expected_discount',
        'customer_id',
        'source_id',
        'status'
    ];

    protected $casts = [
        'joining_date' => 'date',
        'expected_contract_amount' => 'decimal:2',
        'expected_discount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function source()
    {
        return $this->belongsTo(Source::class);
    }

    public function timelines(): HasMany
    {
        return $this->hasMany(InquiriesTimeLine::class, 'inquirie_id');
    }
}
