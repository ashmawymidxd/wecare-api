<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Customer extends Model
{
    use HasFactory;

   protected $fillable = [
        'name',
        'mobile',
        'email',
        'nationality',
        'preferred_language',
        'address',
        'company_name',
        'business_category',
        'country',
        'joining_date',
        'source_type',
        'profile_image'
    ];

    protected $dates = ['joining_date'];

    public function getProfileImageUrlAttribute()
    {
        return $this->profile_image ? Storage::url($this->profile_image) : null;
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
