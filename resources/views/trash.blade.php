<!DOCTYPE html>
<html lang="en" data-theme="{{ auth()->user()->theme ?? 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Trash - Task Manager</title>
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
                <a href="{{ route('bookmarks.index') }}" class="menu-item">
                    <i class="fas fa-bookmark"></i>
                    <span>Bookmarks</span>
                </a>
                <a href="{{ route('trash.index') }}" class="menu-item active">
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
                    <i class="fas fa-trash"></i>
                    Trash
                </h1>
                <p style="color: var(--text-secondary); margin-top: 8px; font-size: 14px;">
                    Deleted tasks are moved here. Only Super Admin can restore or permanently delete tasks.
                </p>
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
                            <th>Deleted Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tasks as $task)
                        <tr>
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
                                {{ $task->deleted_at->format('M d, Y h:i A') }}
                            </td>
                            <td>
                                @if(auth()->user()->isSuperAdmin())
                                <div class="task-actions" style="display: flex; gap: 8px;">
                                    <form action="{{ route('trash.restore', $task->id) }}" method="POST" style="display: inline;" class="restore-form">
                                        @csrf
                                        <button type="submit" class="btn btn-sm" onclick="event.preventDefault(); confirmRestore(this);" style="background: #d1fae5; color: #065f46;" title="Restore">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('trash.force-delete', $task->id) }}" method="POST" style="display: inline;" class="force-delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="event.preventDefault(); confirmForceDelete(this);" title="Permanently Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                                @else
                                <span style="color: #94a3b8; font-size: 12px;">No actions</span>
                                @endif
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
                    <i class="fas fa-trash"></i>
                    <h3>Trash is empty</h3>
                    <p>Deleted tasks will appear here</p>
                </div>
                @endif
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }

        // SweetAlert2 Functions
        function confirmForceDelete(button) {
            const form = button.closest('form');
            Swal.fire({
                title: 'Are you sure?',
                text: "This will permanently delete the task. This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete permanently!',
                cancelButtonText: 'Cancel',
                dangerMode: true
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }

        function confirmRestore(button) {
            const form = button.closest('form');
            Swal.fire({
                title: 'Restore Task?',
                text: "This task will be restored and moved back to active tasks.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, restore it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }

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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>

