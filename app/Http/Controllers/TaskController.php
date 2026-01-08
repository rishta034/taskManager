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
    public function index()
    {
        $user = auth()->user();
        $query = Task::with(['user', 'organization', 'employee.user', 'employee.department']);
        
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
        
        $tasks = $query->latest()->paginate(10);
        $organizations = MasterOrganization::orderBy('name')->get();
        $employees = Employee::with('user')->where('status', 'active')->orderBy('first_name')->get();
        
        // Get bookmarked task IDs for current user
        $bookmarkedTaskIds = \App\Models\TaskBookmark::where('user_id', $user->id)->pluck('task_id')->toArray();
        
        // Get bookmark count
        $bookmarkCount = count($bookmarkedTaskIds);
        
        // Get unread notification count
        $unreadNotificationCount = $user->unreadNotifications()->count();
        
        // Get trash count (only for super admin)
        $trashCount = 0;
        if ($user->isSuperAdmin()) {
            $trashCount = Task::onlyTrashed()->count();
        }
        
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
            'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
            'in_progress' => (clone $statsQuery)->where('status', 'in_progress')->count(),
            'completed' => (clone $statsQuery)->where('status', 'completed')->count(),
        ];
        
        return view('dashboard', compact('tasks', 'stats', 'organizations', 'employees', 'bookmarkedTaskIds', 'bookmarkCount', 'unreadNotificationCount', 'trashCount'));
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
            'status' => 'required|in:pending,in_progress,completed',
            'priority' => 'required|in:low,medium,high',
            'due_date' => 'nullable|date',
            'organization_id' => 'nullable|exists:master_organization,id',
            'visible_to_admin' => 'nullable|boolean',
        ]);

        $validated['user_id'] = auth()->id() ?? 1; // Default to user 1 if no auth
        
        // Only super admin can set visible_to_admin
        if (!$user->isSuperAdmin()) {
            $validated['visible_to_admin'] = true; // Default to visible
        } else {
            $validated['visible_to_admin'] = $request->has('visible_to_admin') ? (bool)$request->visible_to_admin : true;
        }

        $task = Task::create($validated);

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
            'status' => 'required|in:pending,in_progress,completed',
            'priority' => 'required|in:low,medium,high',
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

        $task->update($validated);

        // Redirect based on organization
        if ($task->organization_id) {
            return redirect()->route('organization.tasks', $task->organization_id)->with('success', 'Task updated successfully!');
        }

        return redirect()->route('dashboard')->with('success', 'Task updated successfully!');
    }

    public function show(Task $task)
    {
        $user = auth()->user();
        $task->load(['employee', 'organization', 'user']);
        
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
            'visible_to_admin' => $task->visible_to_admin,
            'created_at' => $task->created_at ? $task->created_at->toISOString() : null,
            'updated_at' => $task->updated_at ? $task->updated_at->toISOString() : null,
            'organization' => $task->organization ? $task->organization->name : null,
            'employee' => $task->employee ? $task->employee->first_name . ' ' . $task->employee->last_name : null,
            'created_by' => $task->user ? $task->user->name : null,
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

    public function organizationTasks($organizationId)
    {
        $user = auth()->user();
        $organization = MasterOrganization::findOrFail($organizationId);
        
        $query = Task::with(['user', 'organization', 'employee.user', 'employee.department'])
            ->where('organization_id', $organizationId);
        
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
        
        $tasks = $query->latest()->paginate(10);
        
        $organizations = MasterOrganization::orderBy('name')->get();
        $employees = Employee::with('user')->where('status', 'active')->orderBy('first_name')->get();
        
        // Get bookmarked task IDs for current user
        $bookmarkedTaskIds = \App\Models\TaskBookmark::where('user_id', $user->id)->pluck('task_id')->toArray();
        
        // Get bookmark count
        $bookmarkCount = count($bookmarkedTaskIds);
        
        // Get unread notification count
        $unreadNotificationCount = $user->unreadNotifications()->count();
        
        // Get trash count (only for super admin)
        $trashCount = 0;
        if ($user->isSuperAdmin()) {
            $trashCount = Task::onlyTrashed()->count();
        }
        
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
            'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
            'in_progress' => (clone $statsQuery)->where('status', 'in_progress')->count(),
            'completed' => (clone $statsQuery)->where('status', 'completed')->count(),
        ];
        
        return view('organization-dashboard', compact('tasks', 'stats', 'organizations', 'organization', 'employees', 'bookmarkedTaskIds', 'bookmarkCount', 'unreadNotificationCount', 'trashCount'));
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

        // Check if there's a paused session
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
            // Create new session
            WorkSession::create([
                'task_id' => $task->id,
                'user_id' => $user->id,
                'started_at' => now(),
                'is_running' => true,
                'total_seconds' => 0,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Work started',
            'started_at' => now()->toISOString()
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

        return response()->json([
            'success' => true,
            'message' => 'Work paused',
            'total_seconds' => $newTotalSeconds
        ]);
    }

    public function getWorkStatus(Task $task)
    {
        $user = auth()->user();
        $activeSession = $task->activeWorkSession($user->id);
        $totalSeconds = $task->getTotalWorkTime($user->id);

        $response = [
            'is_running' => false,
            'total_seconds' => $totalSeconds,
            'started_at' => null,
        ];

        if ($activeSession) {
            $response['is_running'] = true;
            $response['started_at'] = $activeSession->started_at->toISOString();
            
            // Calculate current running time
            $currentSeconds = now()->diffInSeconds($activeSession->started_at);
            $response['current_session_seconds'] = $currentSeconds;
            $response['total_seconds'] = $activeSession->total_seconds + $currentSeconds;
        }

        return response()->json($response);
    }
}
