<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskBookmark;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    public function toggle(Request $request, Task $task)
    {
        $user = auth()->user();
        $bookmark = TaskBookmark::where('user_id', $user->id)
            ->where('task_id', $task->id)
            ->first();

        if ($bookmark) {
            $bookmark->delete();
            return response()->json(['bookmarked' => false, 'message' => 'Bookmark removed']);
        } else {
            TaskBookmark::create([
                'user_id' => $user->id,
                'task_id' => $task->id,
            ]);
            return response()->json(['bookmarked' => true, 'message' => 'Task bookmarked']);
        }
    }

    public function index()
    {
        $user = auth()->user();
        $bookmarkedTaskIds = TaskBookmark::where('user_id', $user->id)->pluck('task_id');
        
        $query = Task::with(['user', 'organization', 'employee.user', 'employee.department'])
            ->whereIn('id', $bookmarkedTaskIds);

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

        return view('bookmarks', compact('tasks', 'organizations', 'employees'));
    }

    public function count()
    {
        $user = auth()->user();
        $bookmarkedTaskIds = TaskBookmark::where('user_id', $user->id)->pluck('task_id');
        
        // Filter based on role to get actual visible bookmarks
        $query = Task::whereIn('id', $bookmarkedTaskIds);
        
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

