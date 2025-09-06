<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerNote extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id', 'note', 'note_date' , 'note_time'];

    protected $dates = ['note_date'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
