<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HRRule extends Model
{
    use SoftDeletes;
    protected $table = 'hr_rules';  // Explicitly defining the table name is optional if it follows Laravel's naming convention

    // Specify the fields that are mass assignable
    protected $fillable = ['rule'];

    // If you have date fields, Laravel will automatically treat them as instances of Carbon if you specify them
    protected $dates = ['created_at', 'updated_at'];
}
