<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class AuthenticationAccount extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;
    
    public $guard_name = 'api';

    protected $fillable = [
        'employee_id',
        'email',
        'password',
        'secret_question',
        'secret_answer',
        'is_active'
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'secret_answer',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function employee()
    {
        return $this->hasOne(Employee::class, 'authentication_account_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($account) {
            $account->employee()->delete();
        });

        static::restoring(function ($account) {
            if ($account->employee()->withTrashed()->exists()) {
                $account->employee()->restore();
            }
        });
    }
}
