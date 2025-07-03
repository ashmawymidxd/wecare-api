<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Office extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'office_type',
        'total_desks',
        'number_of_reserved_desks',
        'number_of_availability_desks',
        'status'
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
