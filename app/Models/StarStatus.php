<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\StoreEmployee;

class StarStatus extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'star_statuses';

    protected $fillable = [
        'name',
        'reason',
        'status'
    ];

    public function storeEmployee()
    {
        return $this->belongsTo(StoreEmployee::class);
    }
}
