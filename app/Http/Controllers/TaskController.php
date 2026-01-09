<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\MasterOrganization;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\WorkSession;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Task::with(['user', 'organization', 'employee.user', 'employee.department', 'assignedBy']);
        
        // Filter by employee_id if provided (from search)
        if ($request->has('employee_id') && $request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        } else {
            // Filter tasks based on user role
            if ($user->isUser()) {
                // Users see only tasks assigned to them
                $employee = Employee::where('user_id', $user->id)->first();
                if ($employee) {
                    $query->where('employee_id', $employee->id);
                } else {
                    // If user has no employee record, show no tasks
                    $query->whereRaw('1 = 0');
                }
            } elseif ($user->isAdmin()) {
                // Admin sees all tasks visible to admin
                $query->where('visible_to_admin', true);
            }
            // Super admin sees all tasks (no filter)
        }
        
        $tasks = $query->latest()->paginate(10);
        $organizations = MasterOrganization::orderBy('name')->get();
        $employees = Employee::with('user')->where('status', 'active')->orderBy('first_name')->get();
        
        // Get critical task IDs for current user (manually marked + critical priority)
        $manualCriticalTaskIds = \App\Models\TaskCriticalTask::where('user_id', $user->id)->pluck('task_id')->toArray();
        $criticalPriorityTaskIds = Task::where('priority', 'critical')->pluck('id')->toArray();
        $criticalTaskIds = array_unique(array_merge($manualCriticalTaskIds, $criticalPriorityTaskIds));
        
        // Get critical task count
        $criticalTaskCount = count($criticalTaskIds);
        
        // Get tasks with work sessions for current user (to show complete button even for completed tasks)
        $tasksWithWorkSessions = WorkSession::where('user_id', $user->id)
            ->whereIn('task_id', $tasks->pluck('id'))
            ->pluck('task_id')
            ->unique()
            ->toArray();
        
        // Get unread notification count
        $unreadNotificationCount = $user->unreadNotifications()->count();
        
        // Get trash count (only for super admin)
        $trashCount = 0;
        if ($user->isSuperAdmin()) {
            $trashCount = Task::onlyTrashed()->count();
        }
        
        // Calculate stats based on role or employee filter
        $statsQuery = Task::query();
        
        // Filter by employee_id if provided (from search)
        if ($request->has('employee_id') && $request->employee_id) {
            $statsQuery->where('employee_id', $request->employee_id);
        } else {
            if ($user->isUser()) {
                $employee = Employee::where('user_id', $user->id)->first();
                if ($employee) {
                    $statsQuery->where('employee_id', $employee->id);
                } else {
                    $statsQuery->whereRaw('1 = 0');
                }
            } elseif ($user->isAdmin()) {
                $statsQuery->where('visible_to_admin', true);
            }
        }
        
        $stats = [
            'total' => $statsQuery->count(),
            'not_started' => (clone $statsQuery)->where('status', 'not_started')->count(),
            'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
            'in_progress' => (clone $statsQuery)->where('status', 'in_progress')->count(),
            'issue_in_working' => (clone $statsQuery)->where('status', 'issue_in_working')->count(),
            'completed' => (clone $statsQuery)->where('status', 'completed')->count(),
            'incomplete' => (clone $statsQuery)->where('status', 'incomplete')->count(),
            'on_hold' => (clone $statsQuery)->where('status', 'on_hold')->count(),
        ];
        
        return view('dashboard', compact('tasks', 'stats', 'organizations', 'employees', 'criticalTaskIds', 'criticalTaskCount', 'unreadNotificationCount', 'trashCount', 'tasksWithWorkSessions'));
    }

    public function getStats()
    {
        $user = auth()->user();
        
        // Calculate stats based on role
        $statsQuery = Task::query();
        if ($user->isUser()) {
            $employee = Employee::where('user_id', $user->id)->first();
            if ($employee) {
                $statsQuery->where('employee_id', $employee->id);
            } else {
                $statsQuery->whereRaw('1 = 0');
            }
        } elseif ($user->isAdmin()) {
            $statsQuery->where('visible_to_admin', true);
        }
        
        $stats = [
            'total' => $statsQuery->count(),
            'not_started' => (clone $statsQuery)->where('status', 'not_started')->count(),
            'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
            'in_progress' => (clone $statsQuery)->where('status', 'in_progress')->count(),
            'issue_in_working' => (clone $statsQuery)->where('status', 'issue_in_working')->count(),
            'completed' => (clone $statsQuery)->where('status', 'completed')->count(),
            'incomplete' => (clone $statsQuery)->where('status', 'incomplete')->count(),
            'on_hold' => (clone $statsQuery)->where('status', 'on_hold')->count(),
        ];
        
        return response()->json($stats);
    }

    public function store(Request $request)
    {
        // Only Admin and Super Admin can create tasks
        $user = auth()->user();
        if ($user->isUser()) {
            abort(403, 'You do not have permission to create tasks.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,completed,not_started,issue_in_working,incomplete,on_hold',
            'priority' => 'required|in:low,medium,high,critical',
            'due_date' => 'nullable|date',
            'organization_id' => 'nullable|exists:master_organization,id',
            'visible_to_admin' => 'nullable|boolean',
        ]);

        $validated['user_id'] = auth()->id() ?? 1; // Default to user 1 if no auth
        $validated['assigned_by'] = auth()->id(); // Set assigned_by to current user (task creator)
        
        // Only super admin can set visible_to_admin
        if (!$user->isSuperAdmin()) {
            $validated['visible_to_admin'] = true; // Default to visible
        } else {
            $validated['visible_to_admin'] = $request->has('visible_to_admin') ? (bool)$request->visible_to_admin : true;
        }

        $task = Task::create($validated);
        
        // Auto-add to critical tasks if priority is critical
        if ($validated['priority'] === 'critical') {
            \App\Models\TaskCriticalTask::firstOrCreate([
                'user_id' => $user->id,
                'task_id' => $task->id,
            ]);
        }

        // Redirect based on organization
        if ($task->organization_id) {
            return redirect()->route('organization.tasks', $task->organization_id)->with('success', 'Task created successfully!');
        }

        return redirect()->route('dashboard')->with('success', 'Task created successfully!');
    }

    public function update(Request $request, Task $task)
    {
        // Only Admin and Super Admin can update tasks
        $user = auth()->user();
        if ($user->isUser()) {
            abort(403, 'You do not have permission to update tasks.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,completed,not_started,issue_in_working,incomplete,on_hold',
            'priority' => 'required|in:low,medium,high,critical',
            'due_date' => 'nullable|date',
            'organization_id' => 'nullable|exists:master_organization,id',
            'visible_to_admin' => 'nullable|boolean',
        ]);

        // Only super admin can change visible_to_admin
        if (!$user->isSuperAdmin()) {
            unset($validated['visible_to_admin']); // Remove from validated if not super admin
        } else {
            $validated['visible_to_admin'] = $request->has('visible_to_admin') ? (bool)$request->visible_to_admin : $task->visible_to_admin;
        }

        $oldPriority = $task->priority;
        $task->update($validated);
        
        // Auto-add to critical tasks if priority is critical
        if ($validated['priority'] === 'critical' && $oldPriority !== 'critical') {
            \App\Models\TaskCriticalTask::firstOrCreate([
                'user_id' => $user->id,
                'task_id' => $task->id,
            ]);
        } elseif ($validated['priority'] !== 'critical' && $oldPriority === 'critical') {
            // Remove from critical tasks if priority is changed from critical
            \App\Models\TaskCriticalTask::where('task_id', $task->id)
                ->where('user_id', $user->id)
                ->delete();
        }

        // Redirect based on organization
        if ($task->organization_id) {
            return redirect()->route('organization.tasks', $task->organization_id)->with('success', 'Task updated successfully!');
        }

        return redirect()->route('dashboard')->with('success', 'Task updated successfully!');
    }

    public function show(Task $task)
    {
        $user = auth()->user();
        $task->load(['employee', 'organization', 'user', 'assignedBy']);
        
        // Get work sessions for current user
        $workSessions = WorkSession::where('task_id', $task->id)
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        $totalWorkTime = $task->getTotalWorkTime($user->id);
        $activeSession = $task->activeWorkSession($user->id);
        
        return response()->json([
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
            'priority' => $task->priority,
            'due_date' => $task->due_date ? $task->due_date->format('Y-m-d') : null,
            'organization_id' => $task->organization_id,
            'employee_id' => $task->employee_id,
            'assigned_by' => $task->assigned_by,
            'visible_to_admin' => $task->visible_to_admin,
            'created_at' => $task->created_at ? $task->created_at->toISOString() : null,
            'updated_at' => $task->updated_at ? $task->updated_at->toISOString() : null,
            'organization' => $task->organization ? $task->organization->name : null,
            'employee' => $task->employee ? $task->employee->first_name . ' ' . $task->employee->last_name : null,
            'created_by' => $task->user ? $task->user->name : null,
            'assigned_by_name' => $task->assignedBy ? $task->assignedBy->name : null,
            'work_sessions' => $workSessions->map(function($session) {
                return [
                    'id' => $session->id,
                    'started_at' => $session->started_at ? $session->started_at->toISOString() : null,
                    'paused_at' => $session->paused_at ? $session->paused_at->toISOString() : null,
                    'total_seconds' => $session->total_seconds,
                    'is_running' => $session->is_running,
                    'formatted_time' => $session->formatted_time,
                ];
            }),
            'total_work_time' => $totalWorkTime,
            'active_session' => $activeSession ? [
                'started_at' => $activeSession->started_at->toISOString(),
                'total_seconds' => $activeSession->total_seconds,
            ] : null,
        ]);
    }

    public function destroy(Task $task)
    {
        // Only super admin can delete tasks
        $user = auth()->user();
        if (!$user->isSuperAdmin()) {
            return back()->withErrors(['error' => 'You do not have permission to delete tasks.']);
        }

        $organizationId = $task->organization_id;
        $task->delete(); // Soft delete

        // Redirect based on organization
        if ($organizationId) {
            return redirect()->route('organization.tasks', $organizationId)->with('success', 'Task moved to trash successfully!');
        }

        return redirect()->route('dashboard')->with('success', 'Task moved to trash successfully!');
    }

    public function organizationTasks(Request $request, $organizationId)
    {
        $user = auth()->user();
        $organization = MasterOrganization::findOrFail($organizationId);
        
        $query = Task::with(['user', 'organization', 'employee.user', 'employee.department', 'assignedBy'])
            ->where('organization_id', $organizationId);
        
        // Filter by employee_id if provided (from search)
        if ($request->has('employee_id') && $request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        } else {
            // Filter tasks based on user role
            if ($user->isUser()) {
                // Users see only tasks assigned to them
                $employee = Employee::where('user_id', $user->id)->first();
                if ($employee) {
                    $query->where('employee_id', $employee->id);
                } else {
                    $query->whereRaw('1 = 0');
                }
            } elseif ($user->isAdmin()) {
                // Admin sees all tasks visible to admin
                $query->where('visible_to_admin', true);
            }
            // Super admin sees all tasks (no filter)
        }
        
        $tasks = $query->latest()->paginate(10);
        
        $organizations = MasterOrganization::orderBy('name')->get();
        $employees = Employee::with('user')->where('status', 'active')->orderBy('first_name')->get();
        
        // Get critical task IDs for current user (manually marked + critical priority)
        $manualCriticalTaskIds = \App\Models\TaskCriticalTask::where('user_id', $user->id)->pluck('task_id')->toArray();
        $criticalPriorityTaskIds = Task::where('priority', 'critical')->pluck('id')->toArray();
        $criticalTaskIds = array_unique(array_merge($manualCriticalTaskIds, $criticalPriorityTaskIds));
        
        // Get critical task count
        $criticalTaskCount = count($criticalTaskIds);
        
        // Get tasks with work sessions for current user (to show complete button even for completed tasks)
        $tasksWithWorkSessions = WorkSession::where('user_id', $user->id)
            ->whereIn('task_id', $tasks->pluck('id'))
            ->pluck('task_id')
            ->unique()
            ->toArray();
        
        // Get unread notification count
        $unreadNotificationCount = $user->unreadNotifications()->count();
        
        // Get trash count (only for super admin)
        $trashCount = 0;
        if ($user->isSuperAdmin()) {
            $trashCount = Task::onlyTrashed()->count();
        }
        
        // Calculate stats based on role or employee filter
        $statsQuery = Task::where('organization_id', $organizationId);
        
        // Filter by employee_id if provided (from search)
        if ($request->has('employee_id') && $request->employee_id) {
            $statsQuery->where('employee_id', $request->employee_id);
        } else {
            if ($user->isUser()) {
                $employee = Employee::where('user_id', $user->id)->first();
                if ($employee) {
                    $statsQuery->where('employee_id', $employee->id);
                } else {
                    $statsQuery->whereRaw('1 = 0');
                }
            } elseif ($user->isAdmin()) {
                $statsQuery->where('visible_to_admin', true);
            }
        }
        
        $stats = [
            'total' => $statsQuery->count(),
            'not_started' => (clone $statsQuery)->where('status', 'not_started')->count(),
            'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
            'in_progress' => (clone $statsQuery)->where('status', 'in_progress')->count(),
            'issue_in_working' => (clone $statsQuery)->where('status', 'issue_in_working')->count(),
            'completed' => (clone $statsQuery)->where('status', 'completed')->count(),
            'incomplete' => (clone $statsQuery)->where('status', 'incomplete')->count(),
            'on_hold' => (clone $statsQuery)->where('status', 'on_hold')->count(),
        ];
        
        return view('organization-dashboard', compact('tasks', 'stats', 'organizations', 'organization', 'employees', 'criticalTaskIds', 'criticalTaskCount', 'unreadNotificationCount', 'trashCount', 'tasksWithWorkSessions'));
    }

    public function getOrganizationStats($organizationId)
    {
        $user = auth()->user();
        
        // Calculate stats based on role
        $statsQuery = Task::where('organization_id', $organizationId);
        if ($user->isUser()) {
            $employee = Employee::where('user_id', $user->id)->first();
            if ($employee) {
                $statsQuery->where('employee_id', $employee->id);
            } else {
                $statsQuery->whereRaw('1 = 0');
            }
        } elseif ($user->isAdmin()) {
            $statsQuery->where('visible_to_admin', true);
        }
        
        $stats = [
            'total' => $statsQuery->count(),
            'not_started' => (clone $statsQuery)->where('status', 'not_started')->count(),
            'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
            'in_progress' => (clone $statsQuery)->where('status', 'in_progress')->count(),
            'issue_in_working' => (clone $statsQuery)->where('status', 'issue_in_working')->count(),
            'completed' => (clone $statsQuery)->where('status', 'completed')->count(),
            'incomplete' => (clone $statsQuery)->where('status', 'incomplete')->count(),
            'on_hold' => (clone $statsQuery)->where('status', 'on_hold')->count(),
        ];
        
        return response()->json($stats);
    }

    public function assignTask(Request $request, Task $task)
    {
        // Only Admin and Super Admin can assign tasks
        $user = auth()->user();
        if ($user->isUser()) {
            abort(403, 'You do not have permission to assign tasks.');
        }

        $validated = $request->validate([
            'employee_id' => 'nullable|exists:details_employe,id',
        ]);

        $oldEmployeeId = $task->employee_id;
        $newEmployeeId = $validated['employee_id'] ?? null;

        $task->update([
            'employee_id' => $newEmployeeId,
            'assigned_by' => $user->id, // Update assigned_by to current user who is assigning
        ]);

        // Create notification if task is assigned to an employee
        if ($newEmployeeId && $newEmployeeId != $oldEmployeeId) {
            $employee = Employee::find($newEmployeeId);
            if ($employee && $employee->user_id) {
                Notification::create([
                    'user_id' => $employee->user_id,
                    'task_id' => $task->id,
                    'type' => 'task_assigned',
                    'title' => 'New Task Assigned',
                    'message' => "A new task '{$task->title}' has been assigned to you.",
                ]);
            }
        }

        if ($task->organization_id) {
            return redirect()->route('organization.tasks', $task->organization_id)->with('success', 'Task assigned successfully!');
        }

        return redirect()->route('dashboard')->with('success', 'Task assigned successfully!');
    }

    public function startWork(Request $request, Task $task)
    {
        $user = auth()->user();
        
        // Check if user has an active session on this task
        $activeSession = $task->activeWorkSession($user->id);
        if ($activeSession) {
            return response()->json([
                'success' => false,
                'message' => 'Work session already running'
            ], 400);
        }

        // Find all other active work sessions for this user across all tasks
        $otherActiveSessions = WorkSession::where('user_id', $user->id)
            ->where('is_running', true)
            ->where('task_id', '!=', $task->id)
            ->get();

        // Pause all other active sessions and update their task statuses
        $pausedTaskIds = [];
        foreach ($otherActiveSessions as $session) {
            // Calculate elapsed time
            $elapsedSeconds = now()->diffInSeconds($session->started_at);
            $newTotalSeconds = $session->total_seconds + $elapsedSeconds;

            // Pause the session
            $session->update([
                'is_running' => false,
                'paused_at' => now(),
                'total_seconds' => $newTotalSeconds,
            ]);

            // Update task status to 'on_hold' if not completed
            $otherTask = Task::find($session->task_id);
            if ($otherTask && $otherTask->status !== 'completed') {
                $otherTask->update(['status' => 'on_hold']);
                $pausedTaskIds[] = $otherTask->id;
            }
        }

        // Check if there's a paused session for this task
        $pausedSession = WorkSession::where('task_id', $task->id)
            ->where('user_id', $user->id)
            ->where('is_running', false)
            ->latest()
            ->first();

        if ($pausedSession) {
            // Resume paused session
            $pausedSession->update([
                'started_at' => now(),
                'is_running' => true,
                'paused_at' => null,
            ]);
        } else {
            // Create new session (even if task was completed, allow starting work again)
            WorkSession::create([
                'task_id' => $task->id,
                'user_id' => $user->id,
                'started_at' => now(),
                'is_running' => true,
                'total_seconds' => 0,
            ]);
        }

        // Update task status to 'in_progress' when work starts (even if completed)
        $task->update(['status' => 'in_progress']);

        $message = 'Work started';
        if (count($pausedTaskIds) > 0) {
            $message .= '. ' . count($pausedTaskIds) . ' other task(s) automatically put on hold';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'started_at' => now()->toISOString(),
            'status' => 'in_progress',
            'paused_tasks' => $pausedTaskIds
        ]);
    }

    public function pauseWork(Request $request, Task $task)
    {
        $user = auth()->user();
        
        $activeSession = $task->activeWorkSession($user->id);
        if (!$activeSession) {
            return response()->json([
                'success' => false,
                'message' => 'No active work session found'
            ], 400);
        }

        // Calculate elapsed time
        $elapsedSeconds = now()->diffInSeconds($activeSession->started_at);
        $newTotalSeconds = $activeSession->total_seconds + $elapsedSeconds;

        $activeSession->update([
            'is_running' => false,
            'paused_at' => now(),
            'total_seconds' => $newTotalSeconds,
        ]);

        // Update task status to 'on_hold' when work is paused
        $newStatus = $task->status;
        if ($task->status !== 'completed') {
            $task->update(['status' => 'on_hold']);
            $newStatus = 'on_hold';
        }

        return response()->json([
            'success' => true,
            'message' => 'Work paused',
            'total_seconds' => $newTotalSeconds,
            'status' => $newStatus
        ]);
    }

    public function getWorkStatus(Task $task)
    {
        $user = auth()->user();
        $activeSession = $task->activeWorkSession($user->id);
        $totalSeconds = $task->getTotalWorkTime($user->id);
        
        // Check if task is completed and has work sessions (work started after completion)
        $hasWorkSessions = WorkSession::where('task_id', $task->id)
            ->where('user_id', $user->id)
            ->exists();
        $isCompleted = $task->status === 'completed';

        // Check if there's a paused session
        $pausedSession = WorkSession::where('task_id', $task->id)
            ->where('user_id', $user->id)
            ->where('is_running', false)
            ->latest()
            ->first();

        $response = [
            'is_running' => false,
            'is_paused' => false,
            'total_seconds' => $totalSeconds,
            'started_at' => null,
            'is_completed' => $isCompleted,
            'has_work_sessions' => $hasWorkSessions,
        ];

        if ($activeSession) {
            $response['is_running'] = true;
            $response['started_at'] = $activeSession->started_at->toISOString();
            
            // Calculate current running time
            $currentSeconds = now()->diffInSeconds($activeSession->started_at);
            $response['current_session_seconds'] = $currentSeconds;
            $response['total_seconds'] = $activeSession->total_seconds + $currentSeconds;
        } elseif ($pausedSession) {
            // There's a paused session
            $response['is_paused'] = true;
        }

        return response()->json($response);
    }

    public function completeTask(Request $request, Task $task)
    {
        $user = auth()->user();
        
        // Check if user has permission to complete this task
        if ($user->isUser()) {
            $employee = Employee::where('user_id', $user->id)->first();
            if (!$employee || $task->employee_id != $employee->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to complete this task.'
                ], 403);
            }
        }

        // Check if user has started working on this task
        $hasWorkSessions = WorkSession::where('task_id', $task->id)
            ->where('user_id', $user->id)
            ->exists();
        
        if (!$hasWorkSessions) {
            return response()->json([
                'success' => false,
                'message' => 'You must start working on the task before completing it.'
            ], 400);
        }

        // Stop any running work sessions when completing the task
        $activeSession = $task->activeWorkSession($user->id);
        if ($activeSession) {
            // Calculate elapsed time and save it
            $elapsedSeconds = now()->diffInSeconds($activeSession->started_at);
            $newTotalSeconds = $activeSession->total_seconds + $elapsedSeconds;
            
            $activeSession->update([
                'is_running' => false,
                'paused_at' => now(),
                'total_seconds' => $newTotalSeconds,
            ]);
        }

        // Allow completing even if already completed (re-completing after starting work again)
        $wasCompleted = $task->status === 'completed';
        
        // Update task status to 'completed'
        $task->update([
            'status' => 'completed',
        ]);

        // Create notification if task was assigned to someone else (only if it wasn't already completed)
        if (!$wasCompleted && $task->employee_id && $task->employee) {
            $employee = Employee::find($task->employee_id);
            if ($employee && $employee->user_id && $employee->user_id != $user->id) {
                Notification::create([
                    'user_id' => $employee->user_id,
                    'task_id' => $task->id,
                    'type' => 'task_completed',
                    'title' => 'Task Completed',
                    'message' => "Task '{$task->title}' has been marked as completed.",
                ]);
            }
        }

        $message = $wasCompleted 
            ? 'Task re-completed successfully!' 
            : 'Task marked as completed successfully!';

        return response()->json([
            'success' => true,
            'message' => $message,
            'status' => 'completed'
        ]);
    }

    public function incompleteTask(Request $request, Task $task)
    {
        $user = auth()->user();
        
        // Only Admin and Super Admin can mark tasks as incomplete
        if ($user->isUser()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to mark tasks as incomplete.'
            ], 403);
        }

        // Update task status to 'incomplete'
        $task->update([
            'status' => 'incomplete',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Task marked as incomplete successfully!',
            'status' => 'incomplete'
        ]);
    }
}
