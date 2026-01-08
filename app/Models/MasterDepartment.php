<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterDepartment extends Model
{
    use HasFactory;

    protected $table = 'master_department';

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class, 'department_id');
    }
}
