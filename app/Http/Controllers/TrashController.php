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
