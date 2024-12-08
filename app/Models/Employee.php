<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes; 

    protected $fillable = [
        'employee_id',
        'first_name',
        'last_name',
        'position',
        'department',
        'authentication_account_id', // Foreign key to AuthenticationAccount
    ];

    public function authenticationAccount()
    {
        return $this->belongsTo(AuthenticationAccount::class);
    }
}
