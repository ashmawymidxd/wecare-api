<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'room_number'
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function offices()
    {
        return $this->hasMany(Office::class);
    }
}
