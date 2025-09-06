<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasCustomerStatistics;
class Employee extends Authenticatable implements JWTSubject
{
    use Notifiable , HasFactory , HasCustomerStatistics;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'profile_image',
        'nationality',
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

    public function contracts()
    {
        return $this->hasManyThrough(Contract::class, Customer::class);
    }

    public function customersContracts()
    {
        return $this->hasManyThrough(
            Contract::class,
            Customer::class,
            'employee_id', // Foreign key on customers table
            'customer_id', // Foreign key on contracts table
            'id',          // Local key on employees table
            'id'           // Local key on customers table
        );
    }

    public function attachments()
    {
        return $this->hasMany(EmployeeAttachment::class);
    }

    public function hasPermission($permission)
    {
        return in_array($permission, $this->role->permissions ?? []);
    }

    // In your Employee model (Employee.php)

    public function transferCustomersTo(Employee $newEmployee)
    {
        // Validate the new employee exists
        if (!$newEmployee->exists) {
            throw new \InvalidArgumentException("Target employee does not exist");
        }

        // Don't allow transferring to the same employee
        if ($this->id === $newEmployee->id) {
            throw new \InvalidArgumentException("Cannot transfer customers to the same employee");
        }

        // Get the count for return value before updating
        $customerCount = $this->customers()->count();

        // Perform the mass update
        $this->customers()->update(['employee_id' => $newEmployee->id]);

        return $customerCount;
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    // Add this method to get attendance records
    public function getAttendance()
    {
        return $this->attendances()->orderBy('login_time', 'desc')->get();
    }

}
