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

      public function getCustomerStatistics()
    {
        // Total customer count
        $currentCount = $this->customers()->count();

        // Get count from previous month
        $previousMonthCount = $this->customers()
            ->where('created_at', '>=', now()->subMonth()->startOfMonth())
            ->where('created_at', '<', now()->subMonth()->endOfMonth())
            ->count();

        // Calculate percentage change
        $percentageChange = 0;
        if ($previousMonthCount > 0) {
            $percentageChange = (($currentCount - $previousMonthCount) / $previousMonthCount) * 100;
        } elseif ($currentCount > 0) {
            $percentageChange = 100; // infinite% growth (from 0 to current)
        }

        // Format the percentage with +/-
        $formattedPercentage = $percentageChange >= 0
            ? '+'.number_format($percentageChange, 0).'%'
            : number_format($percentageChange, 0).'%';

        return [
            'customers_count' => $currentCount,
            'percentage_change' => $formattedPercentage,
            'comparison_text' => "Last Month"
        ];
    }

}
