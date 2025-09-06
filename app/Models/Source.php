<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Source extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone_number',
        'nationality',
        'preferred_language',
        'account_manager_id',
        'last_connect_date',
        'clients_number',
        'source_type'
    ];

    public function accountManager()
    {
        return $this->belongsTo(Employee::class, 'account_manager_id');
    }

    protected $casts = [
        'last_connect_date' => 'date',
    ];

}
