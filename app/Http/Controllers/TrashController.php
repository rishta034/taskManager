<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\MasterOrganization;
use App\Models\Employee;
use Illuminate\Http\Request;

class TrashController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // Only super admin can access trash
        if (!$user->isSuperAdmin()) {
            abort(403, 'Only Super Admin can access trash.');
        }
        
        // onlyTrashed() shows only soft-deleted tasks
        // These are tasks deleted by superadmin and are hidden from all users, admins, and superadmins
        // They only appear in this trash view
        $query = Task::onlyTrashed()->with(['user', 'organization', 'employee.user', 'employee.department']);
        $tasks = $query->latest('deleted_at')->paginate(10);
        $organizations = MasterOrganization::orderBy('name')->get();
        $employees = Employee::with('user')->where('status', 'active')->orderBy('first_name')->get();

        return view('trash', compact('tasks', 'organizations', 'employees'));
    }

    public function restore($id)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin()) {
            abort(403, 'Only Super Admin can restore tasks.');
        }

        $task = Task::onlyTrashed()->findOrFail($id);
        
        // Restore: This will make the task visible again to all users based on their roles
        // The task will appear in normal task listings after restoration
        $task->restore();

        return redirect()->route('trash.index')->with('success', 'Task restored successfully!');
    }

    public function forceDelete($id)
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin()) {
            abort(403, 'Only Super Admin can permanently delete tasks.');
        }

        $task = Task::onlyTrashed()->findOrFail($id);
        
        // Permanently delete: This will remove the task from database completely
        // After forceDelete, the task will be removed permanently from all users, admins, and superadmins
        // This cannot be undone
        $task->forceDelete();

        return redirect()->route('trash.index')->with('success', 'Task permanently deleted!');
    }

    public function count()
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin()) {
            return response()->json(['count' => 0]);
        }

        $count = Task::onlyTrashed()->count();
        return response()->json(['count' => $count]);
    }
}
