<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermissionRoleDetail extends Model
{
    use HasFactory;

    protected $fillable = ['permission_id', 'role_id', 'permission_array'];

    protected $casts = [
        'permission_array' => 'array', // Automatically cast JSON to array
    ];
    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }    
}
