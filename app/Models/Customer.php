<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'mobile', 'email', 'nationality', 'preferred_language',
        'address', 'company_name', 'business_category', 'country',
        'joining_date', 'source_id'
    ];

    protected $dates = ['joining_date'];

    public function source()
    {
        return $this->belongsTo(Source::class);
    }

    public function attachments()
    {
        return $this->hasMany(CustomerAttachment::class);
    }

    public function notes()
    {
        return $this->hasMany(CustomerNote::class);
    }
}
