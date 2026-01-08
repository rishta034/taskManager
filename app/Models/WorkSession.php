<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'started_at',
        'paused_at',
        'total_seconds',
        'is_running',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'paused_at' => 'datetime',
        'is_running' => 'boolean',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get total time in formatted string (HH:MM:SS)
     */
    public function getFormattedTimeAttribute()
    {
        $hours = floor($this->total_seconds / 3600);
        $minutes = floor(($this->total_seconds % 3600) / 60);
        $seconds = $this->total_seconds % 60;
        
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }
}
