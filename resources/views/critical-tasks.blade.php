<!DOCTYPE html>
<html lang="en" data-theme="{{ auth()->user()->theme ?? 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Critical Tasks - Task Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-tasks"></i>
                    <span>TaskManager</span>
                </div>
            </div>
            <nav class="sidebar-menu">
                <a href="{{ route('dashboard') }}" class="menu-item">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
                <a href="{{ route('employees.index') }}" class="menu-item">
                    <i class="fas fa-users"></i>
                    <span>Employees</span>
                </a>
                @endif
                <a href="{{ route('critical-tasks.index') }}" class="menu-item active">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Critical Tasks</span>
                </a>
                @if(auth()->user()->isSuperAdmin())
                <a href="{{ route('trash.index') }}" class="menu-item">
                    <i class="fas fa-trash"></i>
                    <span>Trash</span>
                </a>
                @endif
            </nav>
        </aside>

        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-left">
                <button class="mobile-menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            <div class="topbar-right">
                <!-- Search Employee - Only for Admin and Super Admin -->
                @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
                <div class="header-search-wrapper">
                    <div class="header-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="employeeSearchInput" placeholder="Search employee..." autocomplete="off">
                        <div class="search-results" id="employeeSearchResults"></div>
                    </div>
                </div>
                @endif
                <a href="{{ route('dashboard') }}" class="header-icon-btn" title="Back to Dashboard">
                    <i class="fas fa-arrow-left"></i>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>
                    <i class="fas fa-exclamation-triangle"></i>
                    Critical Tasks
                </h1>
            </div>


            <!-- Tasks Section -->
            <div class="task-section">
                @if($tasks->count() > 0)
                <table class="task-table">
                    <thead>
                        <tr>
                            <th>Task</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Organization</th>
                            <th>Due Date</th>
                            <th>Assign To</th>
                            <th>Assign By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tasks as $task)
                        <tr onclick="showTaskTimeline({{ $task->id }})" style="cursor: pointer;">
                            <td>
                                <div class="task-title">{{ $task->title }}</div>
                                @if($task->description)
                                <div class="task-description">{{ \Illuminate\Support\Str::limit($task->description, 50) }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-status {{ $task->status }}">
                                    {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-priority {{ $task->priority }}">
                                    {{ ucfirst($task->priority) }}
                                </span>
                            </td>
                            <td>
                                @if($task->organization)
                                    <span style="color: #475569; font-weight: 500;">{{ $task->organization->name }}</span>
                                @else
                                    <span style="color: #94a3b8;">No organization</span>
                                @endif
                            </td>
                            <td>
                                @if($task->due_date)
                                    {{ $task->due_date->format('M d, Y') }}
                                @else
                                    <span style="color: #94a3b8;">No due date</span>
                                @endif
                            </td>
                            <td>
                                @if($task->employee)
                                    <span class="assigned-employee" data-department="{{ $task->employee->department->name ?? 'No Department' }}">
                                        {{ $task->employee->full_name }}
                                    </span>
                                @else
                                    <span style="color: #94a3b8;">-</span>
                                @endif
                            </td>
                            <td>
                                @if($task->assignedBy)
                                    <span style="color: #475569; font-weight: 500;">{{ $task->assignedBy->name }}</span>
                                @else
                                    <span style="color: #94a3b8;">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="task-actions" onclick="event.stopPropagation();" style="display: flex; gap: 6px; align-items: center;">
                                    <button class="btn btn-sm critical-task-btn" onclick="toggleCriticalTask({{ $task->id }})" 
                                            style="background: #fee2e2; color: #dc2626;" title="Remove from Critical Tasks">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                @if($tasks->hasPages())
                <div style="margin-top: 20px; display: flex; justify-content: center;">
                    {{ $tasks->links() }}
                </div>
                @endif
                @else
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>No critical tasks</h3>
                    <p>Mark tasks as critical to see them here</p>
                </div>
                @endif
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }

        function toggleCriticalTask(taskId) {
            fetch(`/critical-tasks/${taskId}/toggle`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                window.location.reload();
            })
            .catch(error => console.error('Critical task error:', error));
        }

        function showTaskTimeline(taskId) {
            window.location.href = `/dashboard?task_id=${taskId}`;
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Show success message from session
        @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: '{{ session('success') }}',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
        @endif

        // Show error message from session
        @if(session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: '{{ session('error') }}',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
        @endif

        // Employee Search Function - Global
        function viewEmployeeTasks(employeeId) {
            // Filter tasks by employee - redirect to dashboard
            window.location.href = `{{ route('dashboard') }}?employee_id=${employeeId}`;
        }

        @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
        // Employee Search - Only for Admin and Super Admin
        let searchTimeout;
        const employeeSearchInput = document.getElementById('employeeSearchInput');
        if (employeeSearchInput) {
            employeeSearchInput.addEventListener('input', function(e) {
                clearTimeout(searchTimeout);
                const query = e.target.value.trim();
                const resultsDiv = document.getElementById('employeeSearchResults');
                
                if (query.length < 2) {
                    resultsDiv.style.display = 'none';
                    return;
                }

                searchTimeout = setTimeout(() => {
                    fetch(`/search/employee?q=${encodeURIComponent(query)}`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Unauthorized');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.error) {
                                resultsDiv.innerHTML = '<div style="padding: 16px; text-align: center; color: #ef4444;">Unauthorized</div>';
                            } else if (data.length === 0) {
                                resultsDiv.innerHTML = '<div style="padding: 16px; text-align: center; color: var(--text-secondary);">No employees found</div>';
                            } else {
                                resultsDiv.innerHTML = data.map(emp => `
                                    <div class="search-result-item" onclick="viewEmployeeTasks(${emp.id})" title="Click to view ${emp.task_count} task(s) assigned to ${emp.name}">
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <div style="flex: 1;">
                                                <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 4px;">${emp.name}</div>
                                                <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 4px;">${emp.email}</div>
                                                <div style="font-size: 11px; color: var(--text-tertiary);">
                                                    <i class="fas fa-building"></i> ${emp.department}
                                                </div>
                                            </div>
                                            <div style="text-align: right; margin-left: 16px; padding-left: 16px; border-left: 1px solid var(--border-color);">
                                                <div style="font-size: 24px; font-weight: 700; color: #6366f1; line-height: 1;">${emp.task_count}</div>
                                                <div style="font-size: 11px; color: var(--text-secondary); margin-top: 4px;">Tasks</div>
                                            </div>
                                        </div>
                                    </div>
                                `).join('');
                            }
                            resultsDiv.style.display = 'block';
                        })
                        .catch(error => {
                            console.error('Search error:', error);
                            resultsDiv.innerHTML = '<div style="padding: 16px; text-align: center; color: #ef4444;">Error loading results</div>';
                            resultsDiv.style.display = 'block';
                        });
                }, 300);
            });

            // Close search results on outside click
            document.addEventListener('click', function(e) {
                const searchWrapper = document.querySelector('.header-search-wrapper');
                if (searchWrapper && !searchWrapper.contains(e.target)) {
                    const resultsDiv = document.getElementById('employeeSearchResults');
                    if (resultsDiv) {
                        resultsDiv.style.display = 'none';
                    }
                }
            });
        }
        @endif
    </script>
</body>
</html>
