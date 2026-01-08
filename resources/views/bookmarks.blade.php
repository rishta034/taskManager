<!DOCTYPE html>
<html lang="en" data-theme="{{ auth()->user()->theme ?? 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Bookmarked Tasks - Task Manager</title>
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
                <a href="{{ route('bookmarks.index') }}" class="menu-item active">
                    <i class="fas fa-bookmark"></i>
                    <span>Bookmarks</span>
                </a>
                <a href="{{ route('trash.index') }}" class="menu-item">
                    <i class="fas fa-trash"></i>
                    <span>Trash</span>
                </a>
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
                <a href="{{ route('dashboard') }}" class="header-icon-btn" title="Back to Dashboard">
                    <i class="fas fa-arrow-left"></i>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>
                    <i class="fas fa-bookmark"></i>
                    Bookmarked Tasks
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
                                <div class="task-actions" onclick="event.stopPropagation();" style="display: flex; gap: 6px; align-items: center;">
                                    <button class="btn btn-sm bookmark-btn" onclick="toggleBookmark({{ $task->id }})" 
                                            style="background: #fef3c7; color: #f59e0b;" title="Remove Bookmark">
                                        <i class="fas fa-bookmark"></i>
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
                    <i class="fas fa-bookmark"></i>
                    <h3>No bookmarked tasks</h3>
                    <p>Bookmark tasks to see them here</p>
                </div>
                @endif
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }

        function toggleBookmark(taskId) {
            fetch(`/bookmarks/${taskId}/toggle`, {
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
            .catch(error => console.error('Bookmark error:', error));
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
    </script>
</body>
</html>

