<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'user_id',
        'organization_id',
        'employee_id',
        'assigned_by',
        'visible_to_admin',
    ];

    protected $casts = [
        'due_date' => 'date',
        'visible_to_admin' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function organization()
    {
        return $this->belongsTo(MasterOrganization::class, 'organization_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function criticalTasks()
    {
        return $this->hasMany(TaskCriticalTask::class, 'task_id');
    }

    public function isCriticalBy($userId)
    {
        return $this->criticalTasks()->where('user_id', $userId)->exists();
    }

    public function workSessions()
    {
        return $this->hasMany(WorkSession::class);
    }

    public function activeWorkSession($userId)
    {
        return $this->workSessions()
            ->where('user_id', $userId)
            ->where('is_running', true)
            ->first();
    }

    public function getTotalWorkTime($userId)
    {
        $sessions = $this->workSessions()->where('user_id', $userId)->get();
        $totalSeconds = $sessions->sum('total_seconds');
        
        // Add current running session time if any
        $activeSession = $this->activeWorkSession($userId);
        if ($activeSession && $activeSession->started_at) {
            $currentSeconds = now()->diffInSeconds($activeSession->started_at);
            $totalSeconds += $currentSeconds;
        }
        
        return $totalSeconds;
    }
}
