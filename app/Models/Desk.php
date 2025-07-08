<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Desk extends Model
{
    use HasFactory;

    protected $fillable = [
        'office_id',
        'desk_number',
        'status'
    ];

    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    public function contracts()
    {
        return $this->belongsToMany(Contract::class);
    }

    public function currentContract()
    {
        return $this->contracts()
            ->where('end_date', '>', now())
            ->orderBy('end_date', 'desc')
            ->first();
    }
}
