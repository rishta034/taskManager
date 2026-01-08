<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use App\Models\MasterDepartment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user || (!$user->isAdmin() && !$user->isSuperAdmin())) {
                abort(403, 'You do not have permission to access this page.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $employees = Employee::with(['user', 'department'])->latest()->paginate(15);
        return view('employees.index', compact('employees'));
    }

    public function create()
    {
        $departments = MasterDepartment::orderBy('name')->get();
        return view('employees.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:super_admin,admin,user',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'department_id' => 'nullable|exists:master_department,id',
            'status' => 'required|in:active,inactive,terminated',
        ]);

        DB::beginTransaction();
        try {
            // Create user account
            $user = User::create([
                'name' => "{$validated['first_name']} {$validated['last_name']}",
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'theme' => 'light',
            ]);

            // Create employee details
            $employee = Employee::create([
                'user_id' => $user->id,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'department_id' => $validated['department_id'] ?? null,
                'status' => $validated['status'],
            ]);

            DB::commit();

            return redirect()->route('employees.index')->with('success', 'Employee added successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create employee. Please try again.'])->withInput();
        }
    }

    public function show(Employee $employee)
    {
        $employee->load('user');
        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        $employee->load('user');
        $departments = MasterDepartment::orderBy('name')->get();
        return view('employees.edit', compact('employee', 'departments'));
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $employee->user_id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:super_admin,admin,user',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'department_id' => 'nullable|exists:master_department,id',
            'status' => 'required|in:active,inactive,terminated',
        ]);

        DB::beginTransaction();
        try {
            // Update user account
            $userData = [
                'name' => "{$validated['first_name']} {$validated['last_name']}",
                'email' => $validated['email'],
                'role' => $validated['role'],
            ];

            if (!empty($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
            }

            $employee->user->update($userData);

            // Update employee details
            $employee->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'department_id' => $validated['department_id'] ?? null,
                'status' => $validated['status'],
            ]);

            DB::commit();

            return redirect()->route('employees.index')->with('success', 'Employee updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update employee. Please try again.'])->withInput();
        }
    }

    public function destroy(Employee $employee)
    {
        DB::beginTransaction();
        try {
            $userId = $employee->user_id;
            $employee->delete();
            User::find($userId)->delete();
            
            DB::commit();
            return redirect()->route('employees.index')->with('success', 'Employee deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to delete employee. Please try again.']);
        }
    }
}
