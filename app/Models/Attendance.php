<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = ['employee_id', 'login_time', 'logout_time'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
