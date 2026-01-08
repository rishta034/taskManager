<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterOrganization extends Model
{
    use HasFactory;

    protected $table = 'master_organization';

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    public function tasks()
    {
        return $this->hasMany(Task::class, 'organization_id');
    }
}
