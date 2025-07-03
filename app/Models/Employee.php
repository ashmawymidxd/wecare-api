<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends Authenticatable implements JWTSubject
{
    use Notifiable , HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'profile_image',
        'mobile',
        'preferred_language',
        'address',
        'contract_start_date',
        'contract_end_date',
        'salary',
        'commission',
        'labor_card_end_date',
        'passport_end_date',
        'accommodation_end_date',
        'notes'
    ];

    protected $casts = [
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'labor_card_end_date' => 'date',
        'passport_end_date' => 'date',
        'accommodation_end_date' => 'date',
        'salary' => 'decimal:2',
        'commission' => 'decimal:2',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function customers(){
        return $this->hasMany(Customer::class);
    }

    public function attachments()
    {
        return $this->hasMany(EmployeeAttachment::class);
    }

    public function hasPermission($permission)
    {
        return in_array($permission, $this->role->permissions ?? []);
    }
}
