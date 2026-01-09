<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskCriticalTask;
use Illuminate\Http\Request;

class CriticalTaskController extends Controller
{
    public function toggle(Request $request, Task $task)
    {
        $user = auth()->user();
        
        // If task has critical priority, it's always critical and cannot be removed
        if ($task->priority === 'critical') {
            return response()->json([
                'is_critical' => true, 
                'message' => 'This task has critical priority and is always shown in critical tasks'
            ]);
        }
        
        $criticalTask = TaskCriticalTask::where('user_id', $user->id)
            ->where('task_id', $task->id)
            ->first();

        if ($criticalTask) {
            $criticalTask->delete();
            return response()->json(['is_critical' => false, 'message' => 'Critical task removed']);
        } else {
            TaskCriticalTask::create([
                'user_id' => $user->id,
                'task_id' => $task->id,
            ]);
            return response()->json(['is_critical' => true, 'message' => 'Task marked as critical']);
        }
    }

    public function index()
    {
        $user = auth()->user();
        $criticalTaskIds = TaskCriticalTask::where('user_id', $user->id)->pluck('task_id');
        
        $query = Task::with(['user', 'organization', 'employee.user', 'employee.department', 'assignedBy'])
            ->where(function($q) use ($criticalTaskIds) {
                // Include manually marked critical tasks OR tasks with critical priority
                $q->whereIn('id', $criticalTaskIds)
                  ->orWhere('priority', 'critical');
            });

        // Filter based on role
        if ($user->isUser()) {
            $employee = \App\Models\Employee::where('user_id', $user->id)->first();
            if ($employee) {
                $query->where('employee_id', $employee->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif ($user->isAdmin()) {
            $query->where('visible_to_admin', true);
        }

        $tasks = $query->latest()->paginate(10);
        $organizations = \App\Models\MasterOrganization::orderBy('name')->get();
        $employees = \App\Models\Employee::with('user')->where('status', 'active')->orderBy('first_name')->get();

        return view('critical-tasks', compact('tasks', 'organizations', 'employees'));
    }

    public function count()
    {
        $user = auth()->user();
        $criticalTaskIds = TaskCriticalTask::where('user_id', $user->id)->pluck('task_id');
        
        // Filter based on role to get actual visible critical tasks
        // Include manually marked critical tasks OR tasks with critical priority
        $query = Task::where(function($q) use ($criticalTaskIds) {
            $q->whereIn('id', $criticalTaskIds)
              ->orWhere('priority', 'critical');
        });
        
        if ($user->isUser()) {
            $employee = \App\Models\Employee::where('user_id', $user->id)->first();
            if ($employee) {
                $query->where('employee_id', $employee->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif ($user->isAdmin()) {
            $query->where('visible_to_admin', true);
        }
        
        $count = $query->count();
        return response()->json(['count' => $count]);
    }
}

