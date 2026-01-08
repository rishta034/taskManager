<!DOCTYPE html>
<html lang="en" data-theme="{{ auth()->user()->theme ?? 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $organization->name }} - Task Manager</title>
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
                <a href="{{ route('dashboard') }}" class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
                <a href="{{ route('employees.index') }}" class="menu-item {{ request()->routeIs('employees.*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i>
                    <span>Employees</span>
                </a>
                @endif
                <a href="{{ route('bookmarks.index') }}" class="menu-item {{ request()->routeIs('bookmarks.*') ? 'active' : '' }}">
                    <i class="fas fa-bookmark"></i>
                    <span>Bookmarks</span>
                </a>
                @if(auth()->user()->isSuperAdmin())
                <a href="{{ route('trash.index') }}" class="menu-item {{ request()->routeIs('trash.*') ? 'active' : '' }}">
                    <i class="fas fa-trash"></i>
                    <span>Trash</span>
                </a>
                @endif
                <div style="padding: 12px 20px; color: #94a3b8; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 10px;">
                    Organizations
                </div>
                @foreach($organizations as $org)
                <a href="{{ route('organization.tasks', $org->id) }}" class="menu-item {{ request()->routeIs('organization.tasks') && request()->route('organizationId') == $org->id ? 'active' : '' }}">
                    <i class="fas fa-building"></i>
                    <span>{{ $org->name }}</span>
                </a>
                @endforeach
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
                <!-- Language Selector -->
                <div class="header-icon-group">
                    <div class="header-icon-dropdown">
                        <button class="header-icon-btn" onclick="toggleLanguageDropdown()">
                            <i class="fas fa-globe"></i>
                            <span>EN</span>
                            <i class="fas fa-chevron-down" style="font-size: 10px; margin-left: 4px;"></i>
                        </button>
                        <div class="header-dropdown" id="languageDropdown">
                            <a href="#" class="dropdown-item" onclick="changeLanguage('en')">
                                <i class="fas fa-check" style="visibility: hidden;"></i>
                                <span>English</span>
                            </a>
                            <a href="#" class="dropdown-item" onclick="changeLanguage('es')">
                                <i class="fas fa-check" style="visibility: hidden;"></i>
                                <span>Spanish</span>
                            </a>
                            <a href="#" class="dropdown-item" onclick="changeLanguage('fr')">
                                <i class="fas fa-check" style="visibility: hidden;"></i>
                                <span>French</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- View Expand (Fullscreen) -->
                <button class="header-icon-btn" onclick="toggleFullscreen()" title="Toggle Fullscreen">
                    <i class="fas fa-expand"></i>
                </button>

                <!-- Search Employee -->
                <div class="header-search-wrapper">
                    <div class="header-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="employeeSearchInput" placeholder="Search employee..." autocomplete="off">
                        <div class="search-results" id="employeeSearchResults"></div>
                    </div>
                </div>

                <!-- Bookmarks -->
                <a href="{{ route('bookmarks.index') }}" class="header-icon-btn" title="Bookmarked Tasks">
                    <i class="fas fa-bookmark"></i>
                    @if(isset($bookmarkCount) && $bookmarkCount > 0)
                    <span class="notification-badge">{{ $bookmarkCount > 9 ? '9+' : $bookmarkCount }}</span>
                    @endif
                </a>

                <!-- Trash (Super Admin Only) -->
                @if(auth()->user()->isSuperAdmin())
                <a href="{{ route('trash.index') }}" class="header-icon-btn" title="Trash">
                    <i class="fas fa-trash"></i>
                    @if(isset($trashCount) && $trashCount > 0)
                    <span class="notification-badge">{{ $trashCount > 9 ? '9+' : $trashCount }}</span>
                    @endif
                </a>
                @endif

                <!-- Notifications -->
                <div class="header-icon-group">
                    <button class="header-icon-btn notification-btn" onclick="toggleNotificationDropdown()" title="Notifications">
                        <i class="fas fa-bell"></i>
                        @if(isset($unreadNotificationCount) && $unreadNotificationCount > 0)
                        <span class="notification-badge">{{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}</span>
                        @endif
                    </button>
                    <div class="header-dropdown notification-dropdown" id="notificationDropdown">
                        <div class="dropdown-header">
                            <h4>Notifications</h4>
                            <button onclick="markAllNotificationsAsRead()" class="btn-link" style="font-size: 12px;">Mark all as read</button>
                        </div>
                        <div class="notification-list" id="notificationList">
                            <div style="text-align: center; padding: 20px; color: var(--text-secondary);">
                                <i class="fas fa-spinner fa-spin"></i>
                                <p style="margin-top: 8px; font-size: 13px;">Loading...</p>
                            </div>
                        </div>
                        <div class="dropdown-footer">
                            <a href="#" onclick="viewAllNotifications()" class="btn-link">View All</a>
                        </div>
                    </div>
                </div>

                <div class="user-menu">
                    <div class="user-info" onclick="toggleUserMenu()">
                        @if(auth()->user()->avatar)
                            <img src="{{ auth()->user()->avatar }}" alt="{{ auth()->user()->name }}" class="user-avatar">
                        @else
                            <div class="user-avatar-placeholder">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                        @endif
                        <div class="user-details">
                            <span class="user-name">{{ auth()->user()->name }}</span>
                            <span class="user-role">{{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}</span>
                        </div>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="user-dropdown" id="userDropdown">
                        <div class="dropdown-header">
                            <div class="dropdown-user-info">
                                <div class="dropdown-user-name">{{ auth()->user()->name }}</div>
                                <div class="dropdown-user-email">{{ auth()->user()->email }}</div>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item">
                            <i class="fas fa-user"></i>
                            <span>Profile</span>
                        </a>
                        <a href="#" class="dropdown-item">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <div class="theme-toggle" onclick="toggleTheme()">
                            <div class="theme-toggle-label">
                                <i class="fas fa-moon"></i>
                                <span>Dark Mode</span>
                            </div>
                            <div class="theme-switch {{ auth()->user()->theme === 'dark' ? 'active' : '' }}" id="themeSwitch"></div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <form action="{{ route('logout') }}" method="POST" class="dropdown-item-form">
                            @csrf
                            <button type="submit" class="dropdown-item logout-item">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>{{ $organization->name }}</h1>
                @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openModal()">
                        <i class="fas fa-plus"></i>
                        Add New Task
                    </button>
                </div>
                @endif
            </div>


            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Total Tasks</span>
                        <div class="stat-icon total">
                            <i class="fas fa-tasks"></i>
                        </div>
                    </div>
                    <div class="stat-value">{{ $stats['total'] }}</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        All tasks
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Pending Tasks</span>
                        <div class="stat-icon pending">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="stat-value">{{ $stats['pending'] }}</div>
                    <div class="stat-change">
                        <i class="fas fa-info-circle"></i>
                        Needs attention
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">In Progress</span>
                        <div class="stat-icon progress">
                            <i class="fas fa-spinner"></i>
                        </div>
                    </div>
                    <div class="stat-value">{{ $stats['in_progress'] }}</div>
                    <div class="stat-change">
                        <i class="fas fa-info-circle"></i>
                        Active work
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Completed</span>
                        <div class="stat-icon completed">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="stat-value">{{ $stats['completed'] }}</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        Finished tasks
                    </div>
                </div>
            </div>

            <!-- Tasks Section -->
            <div class="task-section">
                <div class="section-header">
                    <h2 class="section-title">{{ $organization->name }} Tasks</h2>
                </div>

                @if($tasks->count() > 0)
                <table class="task-table">
                    <thead>
                        <tr>
                            <th>Task</th>
                            <th>Status</th>
                            <th>Priority</th>
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
                                <div class="task-actions" onclick="event.stopPropagation();" style="display: flex; flex-direction: column; gap: 4px;">
                                    <!-- Work Timer -->
                                    <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                        <button class="btn btn-sm work-start-btn" id="workBtn{{ $task->id }}" onclick="toggleWork({{ $task->id }}, event)" style="background: #10b981; color: white; font-size: 11px; padding: 4px 8px;" title="Start Work">
                                            <i class="fas fa-play"></i> Start
                                        </button>
                                        <span class="work-timer" id="workTimer{{ $task->id }}" style="font-size: 11px; color: #475569; font-weight: 600; min-width: 70px;">00:00:00</span>
                                    </div>
                                    <!-- Other Actions -->
                                    <div style="display: flex; gap: 4px;">
                                    @if(in_array($task->id, $bookmarkedTaskIds))
                                    <button class="btn btn-sm bookmark-btn" onclick="toggleBookmark({{ $task->id }}, event)" style="background: #fef3c7; color: #f59e0b;" title="Remove Bookmark">
                                        <i class="fas fa-bookmark"></i>
                                    </button>
                                    @else
                                    <button class="btn btn-sm bookmark-btn" onclick="toggleBookmark({{ $task->id }}, event)" style="background: #f1f5f9; color: #475569;" title="Bookmark Task">
                                        <i class="far fa-bookmark" style="opacity: 0.5;"></i>
                                    </button>
                                    @endif
                                    @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
                                    <button class="btn btn-sm" onclick="openAssignModal({{ $task->id }})" style="background: #e0e7ff; color: #4338ca;" title="Assign Task">
                                        <i class="fas fa-user-plus"></i>
                                    </button>
                                    <button class="btn btn-sm" onclick="editTask({{ $task->id }})" style="background: #f1f5f9; color: #475569;">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    @if(auth()->user()->isSuperAdmin())
                                    <form action="{{ route('tasks.destroy', $task) }}" method="POST" style="display: inline;" class="delete-task-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="event.preventDefault(); confirmDeleteTask(this);">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                    @else
                                    <span style="color: #94a3b8; font-size: 12px;">View Only</span>
                                    @endif
                                    </div>
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
                    <i class="fas fa-clipboard-list"></i>
                    <h3>No tasks yet</h3>
                    <p>Get started by creating your first task for {{ $organization->name }}!</p>
                </div>
                @endif
            </div>
        </main>
    </div>

    <!-- Task Timeline Detail Modal -->
    <div class="modal" id="taskTimelineModal">
        <div class="modal-content" style="max-width: 800px; max-height: 90vh; overflow-y: auto;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-stream"></i>
                    <span id="timelineTaskTitle">Task Timeline</span>
                </h3>
                <button class="close-btn" onclick="closeTaskTimelineModal()">&times;</button>
            </div>
            <div id="taskTimelineContent" style="padding: 20px 0;">
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: #6366f1;"></i>
                    <p style="margin-top: 16px; color: var(--text-secondary);">Loading timeline...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Task Modal -->
    <div class="modal" id="assignModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Assign Task</h3>
                <button class="close-btn" onclick="closeAssignModal()">&times;</button>
            </div>
            <form id="assignForm" method="POST">
                @csrf
                <input type="hidden" name="task_id" id="assignTaskId">
                <div class="form-group">
                    <label class="form-label">Select Employee</label>
                    <select name="employee_id" id="assignEmployeeId" class="form-select">
                        <option value="">Unassign (No Employee)</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->full_name }} ({{ $employee->user->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn" onclick="closeAssignModal()" style="background: #f1f5f9; color: #475569;">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i>
                        Assign Task
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add/Edit Task Modal -->
    <div class="modal" id="taskModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Add New Task</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="taskForm" method="POST">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <input type="hidden" name="organization_id" id="taskOrganizationId" value="{{ $organization->id }}">
                
                <div class="form-group">
                    <label class="form-label">Task Title *</label>
                    <input type="text" name="title" class="form-input" id="taskTitle" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea" id="taskDescription" rows="4"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Status *</label>
                    <select name="status" class="form-select" id="taskStatus" required>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Priority *</label>
                    <select name="priority" class="form-select" id="taskPriority" required>
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date" class="form-input" id="taskDueDate">
                </div>

                @if(auth()->user()->isSuperAdmin())
                <div class="form-group">
                    <label class="form-label">Visibility to Admin</label>
                    <div style="display: flex; gap: 20px; align-items: center; padding: 12px; background: var(--bg-tertiary); border-radius: 8px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 500;">
                            <input type="radio" name="visible_to_admin" value="1" id="visibleYes" checked>
                            <span>Show to Admin</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 500;">
                            <input type="radio" name="visible_to_admin" value="0" id="visibleNo">
                            <span>Hide from Admin</span>
                        </label>
                    </div>
                </div>
                @endif

                <div class="form-actions">
                    <button type="button" class="btn" onclick="closeModal()" style="background: #f1f5f9; color: #475569;">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Task</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(taskId = null) {
            const modal = document.getElementById('taskModal');
            const form = document.getElementById('taskForm');
            const modalTitle = document.getElementById('modalTitle');
            
            if (taskId) {
                modalTitle.textContent = 'Edit Task';
                form.action = `/tasks/${taskId}`;
                document.getElementById('formMethod').value = 'PUT';
                
                // Fetch task data and populate form
                fetch(`/tasks/${taskId}`)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('taskTitle').value = data.title || '';
                        document.getElementById('taskDescription').value = data.description || '';
                        document.getElementById('taskStatus').value = data.status || 'pending';
                        document.getElementById('taskPriority').value = data.priority || 'medium';
                        document.getElementById('taskOrganizationId').value = data.organization_id || '{{ $organization->id }}';
                        document.getElementById('taskDueDate').value = data.due_date || '';
                        
                        // Set visibility radio button for super admin
                        @if(auth()->user()->isSuperAdmin())
                        if (data.visible_to_admin !== undefined) {
                            if (data.visible_to_admin) {
                                document.getElementById('visibleYes').checked = true;
                                document.getElementById('visibleNo').checked = false;
                            } else {
                                document.getElementById('visibleYes').checked = false;
                                document.getElementById('visibleNo').checked = true;
                            }
                        }
                        @endif
                    })
                    .catch(error => console.error('Error:', error));
            } else {
                modalTitle.textContent = 'Add New Task';
                form.action = '/tasks';
                document.getElementById('formMethod').value = 'POST';
                form.reset();
                document.getElementById('taskOrganizationId').value = '{{ $organization->id }}';
            }
            
            modal.classList.add('active');
        }

        function editTask(taskId) {
            openModal(taskId);
        }

        function closeModal() {
            document.getElementById('taskModal').classList.remove('active');
        }

        // Close modal on outside click
        document.getElementById('taskModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Handle form submission
        document.getElementById('taskForm').addEventListener('submit', function(e) {
            const formMethod = document.getElementById('formMethod').value;
            const formAction = this.action || '/tasks';
            
            if (formMethod === 'PUT') {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('_method', 'PUT');
                
                fetch(formAction, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => {
                    if (response.ok) {
                        closeModal();
                        showSuccessMessage('Task updated successfully!');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showErrorMessage('Failed to update task. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorMessage('An error occurred. Please try again.');
                });
            } else {
                e.preventDefault();
                // For POST, ensure organization_id is set
                const orgId = document.getElementById('taskOrganizationId').value;
                if (!this.querySelector('input[name="organization_id"]')) {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'organization_id';
                    hiddenInput.value = orgId;
                    this.appendChild(hiddenInput);
                }
                
                const formData = new FormData(this);
                fetch(formAction, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => {
                    if (response.ok || response.redirected) {
                        closeModal();
                        showSuccessMessage('Task created successfully!');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showErrorMessage('Failed to create task. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorMessage('An error occurred. Please try again.');
                });
            }
        });

        // Toggle user dropdown menu
        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            const userInfo = document.querySelector('.user-info');
            dropdown.classList.toggle('active');
            userInfo.classList.toggle('active');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.querySelector('.user-menu');
            const dropdown = document.getElementById('userDropdown');
            if (userMenu && !userMenu.contains(event.target)) {
                dropdown.classList.remove('active');
                document.querySelector('.user-info').classList.remove('active');
            }
        });

        // Toggle mobile sidebar
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }

        // Assign Task Modal Functions
        function openAssignModal(taskId) {
            const modal = document.getElementById('assignModal');
            const form = document.getElementById('assignForm');
            const taskIdInput = document.getElementById('assignTaskId');
            const employeeSelect = document.getElementById('assignEmployeeId');
            
            taskIdInput.value = taskId;
            
            // Fetch current task assignment
            fetch(`/tasks/${taskId}`)
                .then(response => response.json())
                .then(data => {
                    employeeSelect.value = data.employee_id || '';
                    form.action = `/tasks/${taskId}/assign`;
                    modal.classList.add('active');
                })
                .catch(error => {
                    console.error('Error:', error);
                    form.action = `/tasks/${taskId}/assign`;
                    modal.classList.add('active');
                });
        }

        function closeAssignModal() {
            document.getElementById('assignModal').classList.remove('active');
        }

        // Handle assign form submission
        document.getElementById('assignForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => {
                if (response.ok || response.redirected) {
                    closeAssignModal();
                    showSuccessMessage('Task assigned successfully!');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showErrorMessage('Failed to assign task. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorMessage('An error occurred. Please try again.');
            });
        });

        // Close assign modal on outside click
        document.getElementById('assignModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAssignModal();
            }
        });

        // Theme toggle functionality
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-theme', newTheme);
            const themeSwitch = document.getElementById('themeSwitch');
            themeSwitch.classList.toggle('active');
            
            // Update theme icon
            const themeIcon = document.querySelector('.theme-toggle-label i');
            themeIcon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            
            // Update label text
            const themeLabel = document.querySelector('.theme-toggle-label span');
            themeLabel.textContent = newTheme === 'dark' ? 'Light Mode' : 'Dark Mode';
            
            // Save to server
            fetch('{{ route("settings.theme") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ theme: newTheme })
            }).catch(error => console.error('Error updating theme:', error));
        }

        // Initialize theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const themeSwitch = document.getElementById('themeSwitch');
            const themeIcon = document.querySelector('.theme-toggle-label i');
            const themeLabel = document.querySelector('.theme-toggle-label span');
            
            if (currentTheme === 'dark') {
                themeSwitch.classList.add('active');
                themeIcon.className = 'fas fa-sun';
                themeLabel.textContent = 'Light Mode';
            } else {
                themeIcon.className = 'fas fa-moon';
                themeLabel.textContent = 'Dark Mode';
            }
        });

        // Task Timeline Modal Functions
        function showTaskTimeline(taskId) {
            const modal = document.getElementById('taskTimelineModal');
            const content = document.getElementById('taskTimelineContent');
            const title = document.getElementById('timelineTaskTitle');
            
            modal.classList.add('active');
            
            // Show loading state
            content.innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: #6366f1;"></i>
                    <p style="margin-top: 16px; color: var(--text-secondary);">Loading timeline...</p>
                </div>
            `;
            
            // Fetch task details and timeline
            fetch(`/tasks/${taskId}`)
                .then(response => response.json())
                .then(data => {
                    title.textContent = data.title || 'Task Timeline';
                    renderTaskTimeline(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                    content.innerHTML = `
                        <div style="text-align: center; padding: 40px;">
                            <i class="fas fa-exclamation-circle" style="font-size: 32px; color: #ef4444;"></i>
                            <p style="margin-top: 16px; color: var(--text-secondary);">Error loading timeline</p>
                        </div>
                    `;
                });
        }

        function renderTaskTimeline(task) {
            const content = document.getElementById('taskTimelineContent');
            const createdDate = task.created_at ? new Date(task.created_at) : null;
            const updatedDate = task.updated_at ? new Date(task.updated_at) : null;
            const dueDate = task.due_date ? new Date(task.due_date) : null;
            
            let timelineHTML = '<div class="task-timeline-detail">';
            
            // Task Details Header
            timelineHTML += `
                <div class="timeline-detail-header" style="background: var(--bg-tertiary); padding: 20px; border-radius: 12px; margin-bottom: 24px;">
                    <h4 style="margin: 0 0 12px 0; color: var(--text-primary); font-size: 18px; font-weight: 700;">${task.title || 'Untitled Task'}</h4>
                    ${task.description ? `<p style="margin: 0; color: var(--text-secondary); line-height: 1.6;">${task.description}</p>` : ''}
                    ${task.organization ? `<p style="margin: 8px 0 0 0; color: var(--text-secondary);"><i class="fas fa-building"></i> ${task.organization}</p>` : ''}
                    ${task.employee ? `<p style="margin: 8px 0 0 0; color: var(--text-secondary);"><i class="fas fa-user"></i> Assigned to: ${task.employee}</p>` : ''}
                </div>
            `;
            
            // Timeline Events
            timelineHTML += '<div class="timeline-detail-events">';
            
            // 1. Task Created
            if (createdDate) {
                timelineHTML += `
                    <div class="timeline-detail-item">
                        <div class="timeline-detail-marker created">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                        <div class="timeline-detail-content">
                            <h5>Task Created</h5>
                            <p>Task was created${task.created_by ? ' by ' + task.created_by : ''}</p>
                            <span class="timeline-detail-date">
                                <i class="fas fa-calendar"></i>
                                ${formatDate(createdDate)}
                            </span>
                        </div>
                    </div>
                `;
            }
            
            // 2. Status Information
            if (task.status) {
                const statusColors = {
                    'pending': '#f59e0b',
                    'in_progress': '#3b82f6',
                    'completed': '#10b981'
                };
                const statusIcons = {
                    'pending': 'fa-clock',
                    'in_progress': 'fa-spinner',
                    'completed': 'fa-check-circle'
                };
                timelineHTML += `
                    <div class="timeline-detail-item">
                        <div class="timeline-detail-marker status" style="background: ${statusColors[task.status] || '#6366f1'};">
                            <i class="fas ${statusIcons[task.status] || 'fa-info-circle'}"></i>
                        </div>
                        <div class="timeline-detail-content">
                            <h5>Current Status</h5>
                            <p><span class="badge badge-status ${task.status}">${formatStatus(task.status)}</span></p>
                            <span class="timeline-detail-date">
                                <i class="fas fa-info-circle"></i>
                                Current status
                            </span>
                        </div>
                    </div>
                `;
            }
            
            // 3. Priority Information
            if (task.priority) {
                timelineHTML += `
                    <div class="timeline-detail-item">
                        <div class="timeline-detail-marker priority">
                            <i class="fas fa-flag"></i>
                        </div>
                        <div class="timeline-detail-content">
                            <h5>Priority</h5>
                            <p><span class="badge badge-priority ${task.priority}">${formatPriority(task.priority)}</span></p>
                        </div>
                    </div>
                `;
            }
            
            // 4. Due Date
            if (dueDate) {
                const isOverdue = dueDate < new Date() && task.status !== 'completed';
                timelineHTML += `
                    <div class="timeline-detail-item">
                        <div class="timeline-detail-marker ${isOverdue ? 'overdue' : 'due'}">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="timeline-detail-content">
                            <h5>Due Date</h5>
                            <p>${formatDate(dueDate)} ${isOverdue ? '<span style="color: #ef4444; font-weight: 600;">(Overdue)</span>' : ''}</p>
                            <span class="timeline-detail-date">
                                <i class="fas fa-clock"></i>
                                ${isOverdue ? 'Past due date' : 'Due date'}
                            </span>
                        </div>
                    </div>
                `;
            }
            
            // 5. Last Updated
            if (updatedDate && createdDate && updatedDate.getTime() !== createdDate.getTime()) {
                timelineHTML += `
                    <div class="timeline-detail-item">
                        <div class="timeline-detail-marker updated">
                            <i class="fas fa-edit"></i>
                        </div>
                        <div class="timeline-detail-content">
                            <h5>Last Updated</h5>
                            <p>Task was last modified</p>
                            <span class="timeline-detail-date">
                                <i class="fas fa-calendar"></i>
                                ${formatDate(updatedDate)} (${getTimeAgo(updatedDate)})
                            </span>
                        </div>
                    </div>
                `;
            }
            
            // 6. Work Sessions
            if (task.work_sessions && task.work_sessions.length > 0) {
                // Total Work Time Summary
                const totalSeconds = task.total_work_time || 0;
                const hours = Math.floor(totalSeconds / 3600);
                const minutes = Math.floor((totalSeconds % 3600) / 60);
                const seconds = totalSeconds % 60;
                const formattedTotal = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                
                timelineHTML += `
                    <div class="timeline-detail-item">
                        <div class="timeline-detail-marker" style="background: #10b981;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="timeline-detail-content">
                            <h5>Total Work Time</h5>
                            <p style="font-size: 18px; font-weight: 700; color: #10b981;">${formattedTotal}</p>
                            <span class="timeline-detail-date">
                                <i class="fas fa-info-circle"></i>
                                Total time tracked
                            </span>
                        </div>
                    </div>
                `;
                
                // Individual Work Sessions
                task.work_sessions.forEach((session, index) => {
                    if (session.started_at) {
                        const startDate = new Date(session.started_at);
                        const sessionTime = session.total_seconds || 0;
                        const sessionHours = Math.floor(sessionTime / 3600);
                        const sessionMinutes = Math.floor((sessionTime % 3600) / 60);
                        const sessionSecs = sessionTime % 60;
                        const formattedSession = `${String(sessionHours).padStart(2, '0')}:${String(sessionMinutes).padStart(2, '0')}:${String(sessionSecs).padStart(2, '0')}`;
                        
                        if (session.is_running) {
                            timelineHTML += `
                                <div class="timeline-detail-item">
                                    <div class="timeline-detail-marker" style="background: #3b82f6;">
                                        <i class="fas fa-play-circle"></i>
                                    </div>
                                    <div class="timeline-detail-content">
                                        <h5>Work Started ${task.work_sessions.length > 1 ? `(Session ${task.work_sessions.length - index})` : ''}</h5>
                                        <p>Work session is currently running</p>
                                        <p style="margin-top: 8px; font-weight: 600; color: #3b82f6;">Time: ${formattedSession} <span style="color: #10b981;">(Running...)</span></p>
                                        <span class="timeline-detail-date">
                                            <i class="fas fa-calendar"></i>
                                            Started ${formatDate(startDate)} (${getTimeAgo(startDate)})
                                        </span>
                                    </div>
                                </div>
                            `;
                        } else if (session.paused_at) {
                            const pausedDate = new Date(session.paused_at);
                            timelineHTML += `
                                <div class="timeline-detail-item">
                                    <div class="timeline-detail-marker" style="background: #f59e0b;">
                                        <i class="fas fa-pause-circle"></i>
                                    </div>
                                    <div class="timeline-detail-content">
                                        <h5>Work Paused ${task.work_sessions.length > 1 ? `(Session ${task.work_sessions.length - index})` : ''}</h5>
                                        <p>Work session was paused</p>
                                        <p style="margin-top: 8px; font-weight: 600; color: #f59e0b;">Time: ${formattedSession}</p>
                                        <span class="timeline-detail-date">
                                            <i class="fas fa-calendar"></i>
                                            Started ${formatDate(startDate)} - Paused ${formatDate(pausedDate)}
                                        </span>
                                    </div>
                                </div>
                            `;
                        } else {
                            timelineHTML += `
                                <div class="timeline-detail-item">
                                    <div class="timeline-detail-marker" style="background: #10b981;">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="timeline-detail-content">
                                        <h5>Work Completed ${task.work_sessions.length > 1 ? `(Session ${task.work_sessions.length - index})` : ''}</h5>
                                        <p>Work session completed</p>
                                        <p style="margin-top: 8px; font-weight: 600; color: #10b981;">Time: ${formattedSession}</p>
                                        <span class="timeline-detail-date">
                                            <i class="fas fa-calendar"></i>
                                            Started ${formatDate(startDate)}
                                        </span>
                                    </div>
                                </div>
                            `;
                        }
                    }
                });
            } else {
                // No work sessions yet
                timelineHTML += `
                    <div class="timeline-detail-item">
                        <div class="timeline-detail-marker" style="background: #94a3b8;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="timeline-detail-content">
                            <h5>Work Time</h5>
                            <p style="color: var(--text-secondary);">No work time tracked yet</p>
                            <span class="timeline-detail-date">
                                <i class="fas fa-info-circle"></i>
                                Start working to track time
                            </span>
                        </div>
                    </div>
                `;
            }
            
            timelineHTML += '</div></div>';
            content.innerHTML = timelineHTML;
        }

        function formatDate(date) {
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function formatStatus(status) {
            return status.split('_').map(word => 
                word.charAt(0).toUpperCase() + word.slice(1)
            ).join(' ');
        }

        function formatPriority(priority) {
            return priority.charAt(0).toUpperCase() + priority.slice(1);
        }

        function getTimeAgo(date) {
            const seconds = Math.floor((new Date() - date) / 1000);
            const intervals = {
                year: 31536000,
                month: 2592000,
                week: 604800,
                day: 86400,
                hour: 3600,
                minute: 60
            };
            
            for (const [unit, secondsInUnit] of Object.entries(intervals)) {
                const interval = Math.floor(seconds / secondsInUnit);
                if (interval >= 1) {
                    return `${interval} ${unit}${interval > 1 ? 's' : ''} ago`;
                }
            }
            return 'just now';
        }

        function closeTaskTimelineModal() {
            document.getElementById('taskTimelineModal').classList.remove('active');
        }

        // Close timeline modal on outside click
        document.getElementById('taskTimelineModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeTaskTimelineModal();
            }
        });

        // Language Dropdown
        function toggleLanguageDropdown() {
            const dropdown = document.getElementById('languageDropdown');
            dropdown.classList.toggle('active');
        }

        function changeLanguage(lang) {
            // Update the displayed language
            const langBtn = document.querySelector('.header-icon-btn span');
            const langCodes = { 'en': 'EN', 'es': 'ES', 'fr': 'FR' };
            langBtn.textContent = langCodes[lang] || 'EN';
            
            // Update checkmarks
            document.querySelectorAll('#languageDropdown .dropdown-item').forEach(item => {
                const icon = item.querySelector('i');
                icon.style.visibility = 'hidden';
            });
            event.target.closest('.dropdown-item').querySelector('i').style.visibility = 'visible';
            
            // Save language preference (can be implemented with backend)
            localStorage.setItem('preferred_language', lang);
            
            // Close dropdown
            document.getElementById('languageDropdown').classList.remove('active');
            
            // You can add language switching logic here (e.g., reload page with locale)
        }
        
        // Load saved language preference
        document.addEventListener('DOMContentLoaded', function() {
            const savedLang = localStorage.getItem('preferred_language') || 'en';
            const langCodes = { 'en': 'EN', 'es': 'ES', 'fr': 'FR' };
            const langBtn = document.querySelector('.header-icon-btn span');
            if (langBtn) {
                langBtn.textContent = langCodes[savedLang] || 'EN';
            }
            
            // Show checkmark for saved language
            const langItems = document.querySelectorAll('#languageDropdown .dropdown-item');
            langItems.forEach(item => {
                const lang = item.getAttribute('onclick').match(/'([^']+)'/)[1];
                const icon = item.querySelector('i');
                if (lang === savedLang) {
                    icon.style.visibility = 'visible';
                } else {
                    icon.style.visibility = 'hidden';
                }
            });
        });

        // Fullscreen Toggle
        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(err => {
                    console.log('Error attempting to enable fullscreen:', err);
                });
            } else {
                document.exitFullscreen();
            }
        }

        // Employee Search
        let searchTimeout;
        document.getElementById('employeeSearchInput').addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();
            const resultsDiv = document.getElementById('employeeSearchResults');
            
            if (query.length < 2) {
                resultsDiv.style.display = 'none';
                return;
            }

            searchTimeout = setTimeout(() => {
                fetch(`/search/employee?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length === 0) {
                            resultsDiv.innerHTML = '<div style="padding: 16px; text-align: center; color: var(--text-secondary);">No employees found</div>';
                        } else {
                            resultsDiv.innerHTML = data.map(emp => `
                                <div class="search-result-item" onclick="viewEmployeeTasks(${emp.id})">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <div style="font-weight: 600; color: var(--text-primary);">${emp.name}</div>
                                            <div style="font-size: 12px; color: var(--text-secondary);">${emp.email}</div>
                                            <div style="font-size: 11px; color: var(--text-tertiary); margin-top: 4px;">
                                                <i class="fas fa-building"></i> ${emp.department}
                                            </div>
                                        </div>
                                        <div style="text-align: right;">
                                            <div style="font-size: 20px; font-weight: 700; color: var(--accent-primary);">${emp.task_count}</div>
                                            <div style="font-size: 11px; color: var(--text-secondary);">Tasks</div>
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

        document.addEventListener('click', function(e) {
            const searchWrapper = document.querySelector('.header-search-wrapper');
            if (searchWrapper && !searchWrapper.contains(e.target)) {
                document.getElementById('employeeSearchResults').style.display = 'none';
            }
        });

        function viewEmployeeTasks(employeeId) {
            window.location.href = `/dashboard?employee_id=${employeeId}`;
        }

        // Bookmark Toggle
        function toggleBookmark(taskId, event) {
            if (event) {
                event.stopPropagation();
            }
            fetch(`/bookmarks/${taskId}/toggle`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                const btn = event ? event.target.closest('.bookmark-btn') : document.querySelector(`.bookmark-btn[onclick*="${taskId}"]`);
                if (btn) {
                    const icon = btn.querySelector('i');
                    if (data.bookmarked) {
                        btn.style.background = '#fef3c7';
                        btn.style.color = '#f59e0b';
                        icon.style.opacity = '1';
                        icon.className = 'fas fa-bookmark';
                        showSuccessMessage('Task bookmarked!');
                    } else {
                        btn.style.background = '#f1f5f9';
                        btn.style.color = '#475569';
                        icon.style.opacity = '0.5';
                        icon.className = 'far fa-bookmark';
                        showSuccessMessage('Bookmark removed!');
                    }
                } else {
                    // Reload page to update bookmark state
                    window.location.reload();
                }
                // Update bookmark count after toggle
                updateBookmarkCount();
            })
            .catch(error => {
                console.error('Bookmark error:', error);
                showErrorMessage('Failed to update bookmark. Please try again.');
            });
        }

        // Work Timer Functions
        const workTimers = {}; // Store timer intervals for each task

        function formatTime(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
        }

        function updateWorkTimer(taskId, startTime, baseSeconds = 0) {
            const timerElement = document.getElementById(`workTimer${taskId}`);
            if (!timerElement) return;

            const update = () => {
                const now = new Date();
                const start = new Date(startTime);
                const elapsed = Math.floor((now - start) / 1000);
                const total = baseSeconds + elapsed;
                timerElement.textContent = formatTime(total);
            };

            update(); // Initial update
            workTimers[taskId] = setInterval(update, 1000); // Update every second
        }

        function stopWorkTimer(taskId) {
            if (workTimers[taskId]) {
                clearInterval(workTimers[taskId]);
                delete workTimers[taskId];
            }
        }

        function loadWorkStatus(taskId) {
            fetch(`/tasks/${taskId}/work/status`)
                .then(response => response.json())
                .then(data => {
                    const btn = document.getElementById(`workBtn${taskId}`);
                    const timer = document.getElementById(`workTimer${taskId}`);
                    
                    if (!btn || !timer) return;

                    if (data.is_running) {
                        // Update button to pause
                        btn.innerHTML = '<i class="fas fa-pause"></i> Pause';
                        btn.style.background = '#f59e0b';
                        btn.onclick = (e) => { e.stopPropagation(); pauseWork(taskId, e); };
                        
                        // Start timer
                        const startTime = new Date(data.started_at);
                        const baseSeconds = data.total_seconds - Math.floor((new Date() - startTime) / 1000);
                        updateWorkTimer(taskId, data.started_at, Math.max(0, baseSeconds));
                    } else {
                        // Update button to start
                        btn.innerHTML = '<i class="fas fa-play"></i> Start';
                        btn.style.background = '#10b981';
                        btn.onclick = (e) => { e.stopPropagation(); startWork(taskId, e); };
                        
                        // Stop timer and show total
                        stopWorkTimer(taskId);
                        timer.textContent = formatTime(data.total_seconds);
                    }
                })
                .catch(error => console.error('Work status error:', error));
        }

        function startWork(taskId, event) {
            event.stopPropagation();
            fetch(`/tasks/${taskId}/work/start`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadWorkStatus(taskId);
                    showSuccessMessage('Work started!');
                } else {
                    showErrorMessage(data.message || 'Failed to start work');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorMessage('Failed to start work');
            });
        }

        function pauseWork(taskId, event) {
            event.stopPropagation();
            fetch(`/tasks/${taskId}/work/pause`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    stopWorkTimer(taskId);
                    loadWorkStatus(taskId);
                    showSuccessMessage('Work paused!');
                } else {
                    showErrorMessage(data.message || 'Failed to pause work');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorMessage('Failed to pause work');
            });
        }

        // Load work status for all tasks on page load
        document.addEventListener('DOMContentLoaded', function() {
            @foreach($tasks as $task)
            loadWorkStatus({{ $task->id }});
            @endforeach
        });

        // Notification Dropdown
        function toggleNotificationDropdown() {
            const dropdown = document.getElementById('notificationDropdown');
            dropdown.classList.toggle('active');
            
            if (dropdown.classList.contains('active')) {
                loadNotifications();
            }
        }

        function loadNotifications() {
            fetch('/notifications/unread')
                .then(response => response.json())
                .then(data => {
                    const list = document.getElementById('notificationList');
                    if (data.length === 0) {
                        list.innerHTML = '<div style="text-align: center; padding: 40px; color: var(--text-secondary);"><i class="fas fa-bell-slash" style="font-size: 32px; margin-bottom: 12px; opacity: 0.5;"></i><p>No new notifications</p></div>';
                    } else {
                        list.innerHTML = data.slice(0, 5).map(notif => `
                            <div class="notification-item ${!notif.read ? 'unread' : ''}" onclick="markNotificationAsRead(${notif.id})">
                                <div class="notification-icon">
                                    <i class="fas fa-${notif.type === 'task_assigned' ? 'user-plus' : 'info-circle'}"></i>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-title">${notif.title}</div>
                                    <div class="notification-message">${notif.message}</div>
                                    <div class="notification-time">${formatNotificationTime(notif.created_at)}</div>
                                </div>
                            </div>
                        `).join('');
                    }
                })
                .catch(error => {
                    console.error('Notification error:', error);
                    document.getElementById('notificationList').innerHTML = '<div style="padding: 20px; text-align: center; color: #ef4444;">Error loading notifications</div>';
                });
        }

        function markNotificationAsRead(notificationId) {
            fetch(`/notifications/${notificationId}/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(() => {
                loadNotifications();
                updateNotificationCount();
            });
        }

        function markAllNotificationsAsRead() {
            fetch('/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(() => {
                loadNotifications();
                updateNotificationCount();
            });
        }

        function updateNotificationCount() {
            fetch('/notifications/count')
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    const btn = document.querySelector('.notification-btn');
                    if (btn) {
                        let badge = btn.querySelector('.notification-badge');
                        if (data.count > 0) {
                            if (!badge) {
                                badge = document.createElement('span');
                                badge.className = 'notification-badge';
                                btn.appendChild(badge);
                            }
                            badge.textContent = data.count > 9 ? '9+' : data.count;
                        } else {
                            if (badge) badge.remove();
                        }
                    }
                })
                .catch(error => {
                    console.error('Notification count error:', error);
                    // Don't remove badge on error - keep existing state
                });
        }

        function formatNotificationTime(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffMins < 1) return 'Just now';
            if (diffMins < 60) return `${diffMins}m ago`;
            if (diffHours < 24) return `${diffHours}h ago`;
            if (diffDays < 7) return `${diffDays}d ago`;
            return date.toLocaleDateString();
        }

        function viewAllNotifications() {
            window.location.href = '/notifications';
        }

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.header-icon-dropdown')) {
                document.getElementById('languageDropdown').classList.remove('active');
            }
            if (!e.target.closest('.header-icon-group')) {
                document.getElementById('notificationDropdown').classList.remove('active');
            }
        });

        // Update trash count (only for super admin)
        function updateTrashCount() {
            @if(auth()->user()->isSuperAdmin())
            fetch('/trash/count')
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    const trashBtn = document.querySelector('a[href="{{ route("trash.index") }}"]');
                    if (trashBtn) {
                        let badge = trashBtn.querySelector('.notification-badge');
                        if (data.count > 0) {
                            if (!badge) {
                                badge = document.createElement('span');
                                badge.className = 'notification-badge';
                                trashBtn.appendChild(badge);
                            }
                            badge.textContent = data.count > 9 ? '9+' : data.count;
                        } else {
                            if (badge) badge.remove();
                        }
                    }
                })
                .catch(error => {
                    console.error('Trash count error:', error);
                    // Don't remove badge on error - keep existing state
                });
            @endif
        }

        // Update bookmark count
        function updateBookmarkCount() {
            fetch('/bookmarks/count')
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    const bookmarkBtn = document.querySelector('a[href="{{ route("bookmarks.index") }}"]');
                    if (bookmarkBtn) {
                        let badge = bookmarkBtn.querySelector('.notification-badge');
                        if (data.count > 0) {
                            if (!badge) {
                                badge = document.createElement('span');
                                badge.className = 'notification-badge';
                                bookmarkBtn.appendChild(badge);
                            }
                            badge.textContent = data.count > 9 ? '9+' : data.count;
                        } else {
                            if (badge) badge.remove();
                        }
                    }
                })
                .catch(error => {
                    console.error('Bookmark count error:', error);
                    // Don't remove badge on error - keep existing state
                });
        }

        // Update notification count periodically
        setInterval(updateNotificationCount, 30000); // Every 30 seconds
        // Delay initial update to ensure DOM is ready
        setTimeout(updateNotificationCount, 1000);

        // Update trash count periodically (only for super admin)
        @if(auth()->user()->isSuperAdmin())
        setInterval(updateTrashCount, 30000); // Every 30 seconds
        setTimeout(updateTrashCount, 1000);
        @endif

        // Update bookmark count periodically
        setInterval(updateBookmarkCount, 30000); // Every 30 seconds
        setTimeout(updateBookmarkCount, 1000);

        // SweetAlert2 Functions
        function confirmDeleteTask(button) {
            const form = button.closest('form');
            Swal.fire({
                title: 'Are you sure?',
                text: "This task will be moved to trash!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }

        function showSuccessMessage(message) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: message,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        }

        function showErrorMessage(message) {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: message,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
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

