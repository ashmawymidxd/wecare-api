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
        'number_of_desks',
        'status'
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
