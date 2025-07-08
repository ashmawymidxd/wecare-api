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
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

     public function desks()
    {
        return $this->hasMany(Desk::class);
    }

    // Helper method to get the main desk (for private offices)
    // public function getMainDeskAttribute()
    // {
    //     if ($this->office_type === 'private') {
    //         return $this->desks()->firstOrCreate([
    //             'desk_number' => 'main',
    //             'status' => 'available'
    //         ]);
    //     }
    //     return null;
    // }
}
