<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Task;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function searchEmployee(Request $request)
    {
        $search = $request->get('q', '');
        
        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $employees = Employee::with(['user', 'department'])
            ->where('status', 'active')
            ->where(function($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhereHas('user', function($q) use ($search) {
                        $q->where('email', 'like', "%{$search}%")
                          ->orWhere('name', 'like', "%{$search}%");
                    });
            })
            ->limit(10)
            ->get()
            ->map(function($employee) {
                $taskCount = Task::where('employee_id', $employee->id)->count(); // Excludes soft-deleted tasks by default
                return [
                    'id' => $employee->id,
                    'name' => $employee->full_name,
                    'email' => $employee->user->email ?? '',
                    'department' => $employee->department->name ?? 'No Department',
                    'task_count' => $taskCount,
                ];
            });

        return response()->json($employees);
    }
}
