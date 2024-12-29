<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes; 

    protected $fillable = [
        'authentication_account_id',  // Foreign key to AuthenticationAccount
        'firstname',                   // Employee's first name
        'lastname',                    // Employee's last name
        'email_address',               // Unique email address
        'dob',                         // Date of birth
        'nationality',                 // Country of origin
        'address',                     // Residential address
        'city',                        // City name
        'state',                       // State or province
        'zipcode',                     // Postal code
    ];

    /**
     * Relationship to AuthenticationAccount
     */
    public function authenticationAccount()
    {
        return $this->belongsTo(AuthenticationAccount::class);
    }

    /**
     * Relationship to Stores through StoreEmployees
     */
    public function stores()
    {
        return $this->belongsToMany(Store::class, 'store_employees')
            ->withPivot('start_date', 'end_date', 'status')
            ->withTimestamps();
    }

    /**
     * Relationship to Roles through EmployeeRoles
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'employee_roles')
            ->withPivot('assigned_at')
            ->withTimestamps();
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }
    public function borrowedTeamMembers()
    {
        return $this->hasMany(BorrowTeamMember::class, 'employee_id');
    }
}