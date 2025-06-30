<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerAttachment extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id', 'type', 'file_path', 'original_name'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
