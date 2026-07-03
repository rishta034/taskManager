<!DOCTYPE html>
<html lang="en" data-theme="{{ auth()->user()->theme ?? 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Employee Tasks - Task Manager</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-tasks"></i>
                    <span>TaskManager</span>
                </div>
                <button class="sidebar-toggle-btn" onclick="toggleSidebarCollapse()" title="Toggle Sidebar">
                    <i class="fas fa-chevron-left"></i>
                </button>
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
                <a href="{{ route('employee.tasks') }}" class="menu-item {{ request()->routeIs('employee.tasks') ? 'active' : '' }}">
                    <i class="fas fa-user-tasks"></i>
                    <span>Employee Tasks</span>
                </a>
                @endif
                <a href="{{ route('critical-tasks.index') }}" class="menu-item {{ request()->routeIs('critical-tasks.*') ? 'active' : '' }}">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Critical Tasks</span>
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

                <!-- Critical Tasks -->
                <a href="{{ route('critical-tasks.index') }}" class="header-icon-btn" title="Critical Tasks">
                    <i class="fas fa-exclamation-triangle"></i>
                    @if(isset($criticalTaskCount) && $criticalTaskCount > 0)
                    <span class="notification-badge">{{ $criticalTaskCount > 9 ? '9+' : $criticalTaskCount }}</span>
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
                <h1>Employee Tasks</h1>
                @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openModal()">
                        <i class="fas fa-plus"></i>
                        Add New Task
                    </button>
                </div>
                @endif
            </div>

            <!-- Employee Selector -->
            <div style="background: var(--bg-secondary); border-radius: 12px; padding: 20px; margin-bottom: 24px; box-shadow: var(--shadow-card); border: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 300px;">
                        <label for="employeeSelect" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary); font-size: 14px;">
                            <i class="fas fa-user"></i> Select Employee
                        </label>
                        <select id="employeeSelect" name="employee_id" class="form-select" style="width: 100%; padding: 10px 16px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-secondary); color: var(--text-primary); font-size: 14px; cursor: pointer; transition: all 0.3s ease;" onchange="filterByEmployee(this.value)">
                            <option value="">-- Select an Employee --</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ $selectedEmployeeId == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->full_name }} 
                                    @if($employee->user)
                                        ({{ $employee->user->email }})
                                    @endif
                                    @if($employee->department)
                                        - {{ $employee->department->name }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @if($selectedEmployee)
                    <div style="display: flex; align-items: center; gap: 12px; padding: 12px 20px; background: var(--bg-tertiary); border-radius: 8px; border: 1px solid var(--border-color);">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--accent-primary-gradient); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 16px;">
                            {{ strtoupper(substr($selectedEmployee->full_name, 0, 1)) }}
                        </div>
                        <div>
                            <div style="font-weight: 600; color: var(--text-primary); font-size: 14px;">{{ $selectedEmployee->full_name }}</div>
                            <div style="font-size: 12px; color: var(--text-secondary);">
                                @if($selectedEmployee->department)
                                    <i class="fas fa-building"></i> {{ $selectedEmployee->department->name }}
                                @endif
                                @if($selectedEmployee->user)
                                    <span style="margin-left: 8px;"><i class="fas fa-envelope"></i> {{ $selectedEmployee->user->email }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card clickable-stat" onclick="filterTasks('all')" style="cursor: pointer;" title="Click to show all tasks">
                    <div class="stat-header">
                        <span class="stat-title">Total Tasks</span>
                        <div class="stat-icon total">
                            <i class="fas fa-tasks"></i>
                        </div>
                    </div>
                    <div class="stat-value" id="statTotal">{{ $stats['total'] }}</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        All tasks
                    </div>
                </div>

                <div class="stat-card clickable-stat" onclick="filterTasks('critical')" style="cursor: pointer;" title="Click to show critical tasks">
                    <div class="stat-header">
                        <span class="stat-title">Critical Tasks</span>
                        <div class="stat-icon pending">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="stat-value" id="statPending">{{ $stats['pending'] }}</div>
                    <div class="stat-change">
                        <i class="fas fa-info-circle"></i>
                        Critical tasks
                    </div>
                </div>

                <div class="stat-card clickable-stat" onclick="filterTasks('working')" style="cursor: pointer;" title="Click to show working tasks">
                    <div class="stat-header">
                        <span class="stat-title">In Progress</span>
                        <div class="stat-icon progress">
                            <i class="fas fa-spinner"></i>
                        </div>
                    </div>
                    <div class="stat-value" id="statInProgress">{{ $stats['in_progress'] }}</div>
                    <div class="stat-change">
                        <i class="fas fa-info-circle"></i>
                        Working tasks
                    </div>
                </div>

                <div class="stat-card clickable-stat" onclick="filterTasks('completed')" style="cursor: pointer;" title="Click to show completed tasks">
                    <div class="stat-header">
                        <span class="stat-title">Completed</span>
                        <div class="stat-icon completed">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="stat-value" id="statCompleted">{{ $stats['completed'] }}</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        Finished tasks
                    </div>
                </div>

                <div class="stat-card clickable-stat" onclick="filterTasks('incomplete')" style="cursor: pointer;" title="Click to show incomplete tasks">
                    <div class="stat-header">
                        <span class="stat-title">Incomplete Tasks</span>
                        <div class="stat-icon incomplete">
                            <i class="fas fa-times-circle"></i>
                        </div>
                    </div>
                    <div class="stat-value" id="statIncomplete">{{ $stats['incomplete'] }}</div>
                    <div class="stat-change">
                        <i class="fas fa-info-circle"></i>
                        Incomplete tasks
                    </div>
                </div>

                <div class="stat-card clickable-stat" onclick="filterTasks('on_hold')" style="cursor: pointer;" title="Click to show paused and pending tasks">
                    <div class="stat-header">
                        <span class="stat-title">Pause Task</span>
                        <div class="stat-icon pause">
                            <i class="fas fa-pause-circle"></i>
                        </div>
                    </div>
                    <div class="stat-value" id="statOnHold">{{ $stats['on_hold'] }}</div>
                    <div class="stat-change">
                        <i class="fas fa-info-circle"></i>
                        Paused & Pending tasks
                    </div>
                </div>
            </div>

            <!-- Tasks Section -->
            <div class="task-section">
                <!-- Advanced Dashboard Header -->
                <div class="advanced-dashboard-header">
                    <div class="header-content">
                        <div class="header-left">
                            <h2 class="dashboard-title">
                                @if($selectedEmployee)
                                    Tasks for {{ $selectedEmployee->full_name }}
                                @elseif(request()->has('filter') && request()->filter)
                                    @php
                                        $filterLabels = [
                                            'all' => 'All Tasks',
                                            'critical' => 'Critical Tasks',
                                            'working' => 'Working Tasks',
                                            'completed' => 'Completed Tasks',
                                            'incomplete' => 'Incomplete Tasks',
                                            'on_hold' => 'Paused & Pending Tasks'
                                        ];
                                        $currentFilter = request()->filter;
                                    @endphp
                                    {{ $filterLabels[$currentFilter] ?? 'Employee Tasks' }}
                                @else
                                    Employee Tasks - Select an employee to view tasks
                                @endif
                            </h2>
                            <p class="dashboard-subtitle">Click to sort • Filter and search • Pagination enabled</p>
                        </div>
                        <div class="header-right">
                            <div class="header-actions text-light">
                                <button class="header-icon-btn text-light" title="Export to Excel" onclick="exportToExcel()" id="exportExcelBtn"><i class="fas fa-file-excel"></i></button>
                                <button class="header-icon-btn text-light" title="Export to PDF" onclick="exportToPDF()" id="exportPDFBtn"><i class="fas fa-file-pdf"></i></button>
                                <button class="header-icon-btn text-light" title="Print Preview" onclick="printPreview()" id="printBtn"><i class="fas fa-print"></i></button>
                                <button class="header-icon-btn text-light" title="Grid Card View" onclick="toggleGridView()" id="gridViewBtn"><i class="fas fa-th"></i></button>
                                <button class="header-icon-btn text-light" title="List/Table View" onclick="toggleListView()" id="listViewBtn" style="display: none;"><i class="fas fa-list"></i></button>
                                <select class="header-select" id="pageLengthSelect">
                                    <option value="5">5</option>
                                    <option value="10" selected>10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="-1">All</option>
                                </select>
                                <button class="header-icon-btn" title="Decrease Font Size" onclick="decreaseFontSize()" id="decreaseFontBtn"><i class="fas fa-minus"></i></button>
                                <button class="header-icon-btn" title="Increase Font Size" onclick="increaseFontSize()" id="increaseFontBtn"><i class="fas fa-plus"></i></button>
                                <button class="header-icon-btn" title="Toggle Text Wrap" onclick="toggleTextWrap()" id="textWrapBtn"><i class="fas fa-align-justify"></i></button>
                            </div>
                            @if(request()->has('employee_id') && request()->employee_id)
                            <button onclick="window.location.href='{{ route('dashboard') }}'" class="back-btn">
                                <i class="fas fa-arrow-left"></i>
                                <span>Back</span>
                            </button>
                            @elseif(request()->has('filter') && request()->filter)
                            <button onclick="window.location.href='{{ route('dashboard') }}'" class="back-btn">
                                <i class="fas fa-arrow-left"></i>
                                <span>Back</span>
                            </button>
                            @endif
                        </div>
                    </div>
                </div>

                @if($tasks->count() > 0)
                <div class="task-table-wrapper" id="tableWrapper">
                    <table class="task-table advanced-table" id="tasksTable">
                    <thead class="text-nowrap">
                        <tr>
                            <th>TASK</th>
                            <th>STATUS</th>
                            <th>PRIORITY</th>
                            <th>ORGANIZATION</th>
                            <th>DUE DATE</th>
                            <th>ASSIGN TO</th>
                            <th>ASSIGN BY</th>
                            <th>CREATED BY</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tasks as $task)
                        <tr data-task-id="{{ $task->id }}" class="table-row" style="cursor: pointer;">
                            <td>
                                <div class="task-cell-content">
                                    <div class="task-avatar">{{ strtoupper(substr($task->title, 0, 1)) }}</div>
                                    <div class="task-info">
                                        <div class="task-title">{{ $task->title }}</div>
                                        @if($task->description)
                                        <div class="task-description">{{ \Illuminate\Support\Str::limit($task->description, 50) }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <span class="badge badge-status {{ $task->status }}" id="statusBadge{{ $task->id }}">
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
                                    <span style="color: #475569; font-weight: 500; font-size: 11px;">{{ $task->organization->name }}</span>
                                @else
                                    <span style="color: #94a3b8; font-size: 11px;">No organization</span>
                                @endif
                            </td>
                            <td>
                                @if($task->due_date)
                                    <span style="font-size: 11px;">{{ $task->due_date->format('M d, Y') }}</span>
                                @else
                                    <span style="color: #94a3b8; font-size: 11px;">No due date</span>
                                @endif
                            </td>
                            <td>
                                @if($task->employee)
                                    <div class="user-icon-wrapper" style="position: relative; display: inline-block;">
                                        <i class="fas fa-user-circle" style="font-size: 20px; color: #6366f1; cursor: pointer;"></i>
                                        <div class="user-tooltip">
                                            <div class="tooltip-name">{{ $task->employee->full_name }}</div>
                                            <div class="tooltip-designation">{{ $task->employee->department->name ?? 'No Department' }}</div>
                                        </div>
                                    </div>
                                @else
                                    <i class="fas fa-user-slash" style="font-size: 18px; color: #94a3b8;"></i>
                                @endif
                            </td>
                            <td>
                                @if($task->assignedBy)
                                    <div class="user-icon-wrapper" style="position: relative; display: inline-block;">
                                        <i class="fas fa-user-tie" style="font-size: 20px; color: #8b5cf6; cursor: pointer;"></i>
                                        <div class="user-tooltip">
                                            <div class="tooltip-name">{{ $task->assignedBy->name }}</div>
                                            <div class="tooltip-designation">{{ ucfirst(str_replace('_', ' ', $task->assignedBy->role)) }}</div>
                                        </div>
                                    </div>
                                @else
                                    <i class="fas fa-user-slash" style="font-size: 18px; color: #94a3b8;"></i>
                                @endif
                            </td>
                            <td>
                                @if($task->user)
                                    <div class="user-icon-wrapper" style="position: relative; display: inline-block;">
                                        <i class="fas fa-user" style="font-size: 20px; color: #10b981; cursor: pointer;"></i>
                                        <div class="user-tooltip">
                                            <div class="tooltip-name">{{ $task->user->name }}</div>
                                            <div class="tooltip-designation">{{ ucfirst(str_replace('_', ' ', $task->user->role)) }}</div>
                                        </div>
                                    </div>
                                @else
                                    <i class="fas fa-user-slash" style="font-size: 18px; color: #94a3b8;"></i>
                                @endif
                            </td>
                            <td>
                                <div class="task-actions-wrapper" onclick="event.stopPropagation();" style="display: flex; flex-direction: column; gap: 4px;">
                                    <!-- Work Timer -->
                                    <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                        @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
                                            @php
                                                $statusText = '';
                                                $statusClass = '';
                                                if($task->status === 'completed') {
                                                    $statusText = 'Complete';
                                                    $statusClass = 'background: #10b981; color: white;';
                                                } elseif($task->status === 'in_progress') {
                                                    $statusText = 'Running Working';
                                                    $statusClass = 'background: #3b82f6; color: white;';
                                                } elseif($task->status === 'pending') {
                                                    $statusText = 'Pending';
                                                    $statusClass = 'background: #f59e0b; color: white;';
                                                } elseif($task->status === 'on_hold') {
                                                    $statusText = 'On Hold';
                                                    $statusClass = 'background: #f59e0b; color: white;';
                                                } else {
                                                    $statusText = ucfirst(str_replace('_', ' ', $task->status));
                                                    $statusClass = 'background: #94a3b8; color: white;';
                                                }
                                            @endphp
                                            <span class="btn btn-sm" id="workBtn{{ $task->id }}" style="{{ $statusClass }} font-size: 10px; padding: 3px 6px; border: none; cursor: default;">
                                                {{ $statusText }}
                                            </span>
                                        @else
                                            <button class="btn btn-sm work-start-btn" id="workBtn{{ $task->id }}" onclick="startWork({{ $task->id }}, event)" style="background: #10b981; color: white; font-size: 10px; padding: 3px 6px;" title="Start Work">
                                                <i class="fas fa-play"></i> Start
                                            </button>
                                        @endif
                                        <span class="work-timer" id="workTimer{{ $task->id }}" style="font-size: 10px; color: #475569; font-weight: 600; min-width: 70px;">00:00:00</span>
                                    </div>
                                    <!-- Multi Action Button -->
                                    <div class="multi-action" id="multiAction{{ $task->id }}" onclick="event.stopPropagation();">
                                        <div class="action-wrapper">
                                            @if(in_array($task->id, $criticalTaskIds) || $task->priority === 'critical')
                                            <button class="btn-multi-action critical-action" onclick="toggleCriticalTask({{ $task->id }}, event)" title="Remove from Critical"></button>
                                            @else
                                            <button class="btn-multi-action critical-action" onclick="toggleCriticalTask({{ $task->id }}, event)" title="Mark as Critical"></button>
                                            @endif
                                            @if($task->status !== 'completed' || (isset($tasksWithWorkSessions) && in_array($task->id, $tasksWithWorkSessions)))
                                            <button class="btn-multi-action complete-action" onclick="completeTask({{ $task->id }}, event)" title="Complete Task"></button>
                                            @endif
                                            @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
                                            @if($task->status !== 'incomplete')
                                            <button class="btn-multi-action incomplete-action" onclick="incompleteTask({{ $task->id }}, event)" title="Mark as Incomplete"></button>
                                            @endif
                                            <button class="btn-multi-action assign-action" onclick="openAssignModal({{ $task->id }})" title="Assign Task"></button>
                                            <button class="btn-multi-action edit-action" onclick="editTask({{ $task->id }})" title="Edit Task"></button>
                                            @if(auth()->user()->isSuperAdmin())
                                            <button class="btn-multi-action delete-action" onclick="event.preventDefault(); confirmDeleteTask(document.querySelector('#deleteBtn{{ $task->id }}').closest('form'));" title="Delete Task"></button>
                                            <form action="{{ route('tasks.destroy', $task) }}" method="POST" style="display: none;" class="delete-task-form" id="deleteForm{{ $task->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" id="deleteBtn{{ $task->id }}"></button>
                                            </form>
                                            @endif
                                            @endif
                                            <button class="multi-action-trigger" onclick="toggleMultiAction({{ $task->id }}, event)"></button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    </table>
                </div>
                <div id="gridViewContainer" style="display: none;"></div>

                {{-- Laravel pagination removed - DataTables handles pagination --}}
                @else
                <div class="empty-state">
                    <i class="fas fa-clipboard-list" style="font-size: 70px; color: #94a3b8; opacity: 0.5;"></i>
                    <h3>No tasks yet</h3>
                    <p>Get started by creating your first task!</p>
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
                 <i class="fas fa-tasks" style="font-size: 50px; color: #6366f1;"></i>
                <h3 class="modal-title" id="modalTitle">Add New Task</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="taskForm" method="POST">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                
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
                        <option value="not_started">Not Started</option>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="issue_in_working">Issue in Working</option>
                        <option value="incomplete">Incomplete</option>
                        <option value="on_hold">On Hold</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Priority *</label>
                    <select name="priority" class="form-select" id="taskPriority" required>
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Organization</label>
                    <select name="organization_id" class="form-select" id="taskOrganization">
                        <option value="">Select Organization</option>
                        @foreach($organizations as $org)
                            <option value="{{ $org->id }}">{{ $org->name }}</option>
                        @endforeach
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
                        document.getElementById('taskStatus').value = data.status || 'not_started';
                        document.getElementById('taskPriority').value = data.priority || 'medium';
                        document.getElementById('taskOrganization').value = data.organization_id || '';
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
                document.getElementById('taskOrganization').value = '';
            }
            
            modal.classList.add('active');
        }

        function editTask(taskId) {
            // Close multi-action menu
            const multiAction = document.getElementById(`multiAction${taskId}`);
            if (multiAction) {
                multiAction.classList.remove('is-active');
                const trigger = multiAction.querySelector('.multi-action-trigger');
                if (trigger) trigger.classList.remove('is-active');
            }
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
                // For POST, let it submit normally - success message will show on redirect
                // Or handle via AJAX for better UX
                e.preventDefault();
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

        // Toggle sidebar collapse/expand
        function toggleSidebarCollapse() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
            
            // Save state to localStorage
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        }

        // Load sidebar state from localStorage on page load
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            const sidebar = document.getElementById('sidebar');
            if (sidebarCollapsed && sidebar) {
                sidebar.classList.add('collapsed');
            }
        });

        // Assign Task Modal Functions
        function openAssignModal(taskId) {
            // Close multi-action menu
            const multiAction = document.getElementById(`multiAction${taskId}`);
            if (multiAction) {
                multiAction.classList.remove('is-active');
                const trigger = multiAction.querySelector('.multi-action-trigger');
                if (trigger) trigger.classList.remove('is-active');
            }
            
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
                            <p>Task was created</p>
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
                    'not_started': '#94a3b8',
                    'pending': '#f59e0b',
                    'in_progress': '#3b82f6',
                    'issue_in_working': '#ef4444',
                    'incomplete': '#f97316',
                    'on_hold': '#f59e0b',
                    'completed': '#10b981'
                };
                const statusIcons = {
                    'not_started': 'fa-circle',
                    'pending': 'fa-clock',
                    'in_progress': 'fa-spinner',
                    'issue_in_working': 'fa-exclamation-triangle',
                    'incomplete': 'fa-times-circle',
                    'on_hold': 'fa-pause-circle',
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
            
            // 2.5. Task Completed Event (if completed)
            if (task.status === 'completed' && updatedDate) {
                const completedDate = updatedDate;
                timelineHTML += `
                    <div class="timeline-detail-item">
                        <div class="timeline-detail-marker" style="background: #10b981;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="timeline-detail-content">
                            <h5>Task Completed</h5>
                            <p style="color: #10b981; font-weight: 600;">Task was marked as completed</p>
                            <span class="timeline-detail-date">
                                <i class="fas fa-calendar"></i>
                                Completed ${formatDate(completedDate)} (${getTimeAgo(completedDate)})
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
            if (updatedDate && updatedDate.getTime() !== createdDate?.getTime()) {
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

        // Employee Search - Only for Admin and Super Admin
        function viewEmployeeTasks(employeeId) {
            // Filter tasks by employee
            window.location.href = `{{ route('dashboard') }}?employee_id=${employeeId}`;
        }

        @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
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

        // Toggle Multi-Action Button
        function toggleMultiAction(taskId, event) {
            if (event) {
                event.stopPropagation();
            }
            const multiAction = document.getElementById(`multiAction${taskId}`);
            if (!multiAction) return;
            
            const trigger = multiAction.querySelector('.multi-action-trigger');
            const allMultiActions = document.querySelectorAll('.multi-action');

            // Close all other multi-action menus
            allMultiActions.forEach(ma => {
                if (ma.id !== `multiAction${taskId}` && ma.id !== `multiActionCard${taskId}`) {
                    ma.classList.remove('is-active');
                    const otherTrigger = ma.querySelector('.multi-action-trigger');
                    if (otherTrigger) otherTrigger.classList.remove('is-active');
                }
            });

            // Toggle current menu
            if (multiAction && trigger) {
                multiAction.classList.toggle('is-active');
                trigger.classList.toggle('is-active');
            }
        }

        // Separate function for card multi-action
        function toggleMultiActionCard(taskId, event) {
            if (event) {
                event.stopPropagation();
            }
            const multiAction = document.getElementById(`multiActionCard${taskId}`);
            if (!multiAction) {
                console.warn('Multi-action not found for card task:', taskId);
                return;
            }
            
            const trigger = multiAction.querySelector('.multi-action-trigger');
            if (!trigger) {
                console.warn('Trigger not found for card task:', taskId);
                return;
            }
            
            const allMultiActions = document.querySelectorAll('.multi-action');

            // Close all other multi-action menus (both table and card)
            allMultiActions.forEach(ma => {
                if (ma.id !== `multiAction${taskId}` && ma.id !== `multiActionCard${taskId}`) {
                    ma.classList.remove('is-active');
                    const otherTrigger = ma.querySelector('.multi-action-trigger');
                    if (otherTrigger) otherTrigger.classList.remove('is-active');
                }
            });

            // Toggle current menu
            multiAction.classList.toggle('is-active');
            trigger.classList.toggle('is-active');
        }

        // Close multi-action menus when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.multi-action') && 
                !e.target.closest('.task-card-actions') && 
                !e.target.closest('.btn-multi-action') &&
                !e.target.closest('.multi-action-trigger')) {
                document.querySelectorAll('.multi-action').forEach(ma => {
                    ma.classList.remove('is-active');
                    const trigger = ma.querySelector('.multi-action-trigger');
                    if (trigger) trigger.classList.remove('is-active');
                });
            }
        });

        // Critical Task Toggle
        function toggleCriticalTask(taskId, event) {
            if (event) {
                event.stopPropagation();
            }
            // Close multi-action menu (both table and card)
            const multiAction = document.getElementById(`multiAction${taskId}`);
            const multiActionCard = document.getElementById(`multiActionCard${taskId}`);
            if (multiAction) {
                multiAction.classList.remove('is-active');
                const trigger = multiAction.querySelector('.multi-action-trigger');
                if (trigger) trigger.classList.remove('is-active');
            }
            if (multiActionCard) {
                multiActionCard.classList.remove('is-active');
                const triggerCard = multiActionCard.querySelector('.multi-action-trigger');
                if (triggerCard) triggerCard.classList.remove('is-active');
            }
            fetch(`/critical-tasks/${taskId}/toggle`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                // Close action menu
                const menu = document.getElementById(`actionMenu${taskId}`);
                if (menu) menu.style.display = 'none';
                
                // Reload page to update critical task state
                window.location.reload();
            })
            .catch(error => {
                console.error('Critical task error:', error);
                showErrorMessage('Failed to update critical task. Please try again.');
            });
        }

        // Complete Task
        function completeTask(taskId, event) {
            if (event) {
                event.stopPropagation();
            }
            // Close multi-action menu (both table and card)
            const multiAction = document.getElementById(`multiAction${taskId}`);
            const multiActionCard = document.getElementById(`multiActionCard${taskId}`);
            if (multiAction) {
                multiAction.classList.remove('is-active');
                const trigger = multiAction.querySelector('.multi-action-trigger');
                if (trigger) trigger.classList.remove('is-active');
            }
            if (multiActionCard) {
                multiActionCard.classList.remove('is-active');
                const triggerCard = multiActionCard.querySelector('.multi-action-trigger');
                if (triggerCard) triggerCard.classList.remove('is-active');
            }
            fetch(`/tasks/${taskId}/complete`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update status badge if status changed
                    if (data.status) {
                        updateStatusBadge(taskId, data.status);
                    }
                    // Stop work timer and reload work status
                    stopWorkTimer(taskId);
                    loadWorkStatus(taskId);
                    // Update stats cards
                    updateStatsCards();
                    showSuccessMessage(data.message || 'Task completed successfully!');
                } else {
                    showErrorMessage(data.message || 'Failed to complete task. Please try again.');
                }
            })
            .catch(error => {
                console.error('Complete task error:', error);
                showErrorMessage('Failed to complete task. Please try again.');
            });
        }

        // Incomplete Task (Admin and Super Admin only)
        function incompleteTask(taskId, event) {
            if (event) {
                event.stopPropagation();
            }
            // Close multi-action menu (both table and card)
            const multiAction = document.getElementById(`multiAction${taskId}`);
            const multiActionCard = document.getElementById(`multiActionCard${taskId}`);
            if (multiAction) {
                multiAction.classList.remove('is-active');
                const trigger = multiAction.querySelector('.multi-action-trigger');
                if (trigger) trigger.classList.remove('is-active');
            }
            if (multiActionCard) {
                multiActionCard.classList.remove('is-active');
                const triggerCard = multiActionCard.querySelector('.multi-action-trigger');
                if (triggerCard) triggerCard.classList.remove('is-active');
            }
            fetch(`/tasks/${taskId}/incomplete`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update status badge if status changed
                    if (data.status) {
                        updateStatusBadge(taskId, data.status);
                    }
                    // Reload work status
                    loadWorkStatus(taskId);
                    // Update stats cards
                    updateStatsCards();
                    showSuccessMessage(data.message || 'Task marked as incomplete successfully!');
                } else {
                    showErrorMessage(data.message || 'Failed to mark task as incomplete. Please try again.');
                }
            })
            .catch(error => {
                console.error('Incomplete task error:', error);
                showErrorMessage('Failed to mark task as incomplete. Please try again.');
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
            const timerCardElement = document.getElementById(`workTimerCard${taskId}`);
            
            if (!timerElement && !timerCardElement) return;

            const update = () => {
                const now = new Date();
                const start = new Date(startTime);
                const elapsed = Math.floor((now - start) / 1000);
                const total = baseSeconds + elapsed;
                const formattedTime = formatTime(total);
                
                if (timerElement) timerElement.textContent = formattedTime;
                if (timerCardElement) timerCardElement.textContent = formattedTime;
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
                    
                    if (btn && timer) {
                        updateWorkButton(btn, timer, taskId, data);
                    }
                    
                    // Update status badge if task is being worked by another user
                    if (data.is_being_worked_by_other && data.status) {
                        updateStatusBadge(taskId, 'in_progress');
                    }
                    
                    // Also update card view button if exists
                    loadWorkStatusCard(taskId);
                })
                .catch(error => console.error('Work status error:', error));
        }

        function loadWorkStatusCard(taskId) {
            fetch(`/tasks/${taskId}/work/status`)
                .then(response => response.json())
                .then(data => {
                    const btn = document.getElementById(`workBtnCard${taskId}`);
                    const timer = document.getElementById(`workTimerCard${taskId}`);
                    
                    if (btn && timer) {
                        updateWorkButton(btn, timer, taskId, data);
                    }
                })
                .catch(error => console.error('Work status error:', error));
        }

        function updateWorkButton(btn, timer, taskId, data) {
            if (!btn || !timer) return;

            // Helper function to get status text for admin/super admin
            function getStatusTextForAdmin(status) {
                if (status === 'completed') {
                    return 'Complete';
                } else if (status === 'in_progress') {
                    return 'Running Working';
                } else if (status === 'pending') {
                    return 'Pending';
                } else if (status === 'on_hold') {
                    return 'On Hold';
                } else {
                    return status.charAt(0).toUpperCase() + status.slice(1).replace(/_/g, ' ');
                }
            }

            // Helper function to get status style for admin/super admin
            function getStatusStyleForAdmin(status) {
                if (status === 'completed') {
                    return '#10b981';
                } else if (status === 'in_progress') {
                    return '#3b82f6';
                } else if (status === 'pending' || status === 'on_hold') {
                    return '#f59e0b';
                } else {
                    return '#94a3b8';
                }
            }

            // Get task status from status badge
            const statusBadge = document.getElementById(`statusBadge${taskId}`);
            let taskStatus = '';
            if (statusBadge) {
                // Get status from badge classes (e.g., "badge badge-status completed")
                const classes = statusBadge.className.split(' ');
                const statusClass = classes.find(cls => cls !== 'badge' && cls !== 'badge-status');
                if (statusClass) {
                    taskStatus = statusClass;
                } else {
                    // Fallback: get from badge text
                    const badgeText = statusBadge.textContent.trim().toLowerCase().replace(/\s+/g, '_');
                    taskStatus = badgeText;
                }
            }

            // For admin/super admin, show status text instead of buttons
            if (isAdmin || isSuperAdmin) {
                if (data.is_running && !data.is_being_worked_by_other) {
                    // Current admin is working - show Working button
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Working';
                    btn.style.background = '#3b82f6';
                    btn.onclick = (e) => { e.stopPropagation(); pauseWork(taskId, e); };
                    btn.style.cursor = 'pointer';
                    
                    if (data.started_at) {
                        const startTime = new Date(data.started_at);
                        const baseSeconds = data.total_seconds - Math.floor((new Date() - startTime) / 1000);
                        updateWorkTimer(taskId, data.started_at, Math.max(0, baseSeconds));
                    }
                } else {
                    // Show task status text
                    const statusText = getStatusTextForAdmin(taskStatus);
                    const statusColor = getStatusStyleForAdmin(taskStatus);
                    btn.innerHTML = statusText;
                    btn.style.background = statusColor;
                    btn.onclick = (e) => { e.stopPropagation(); };
                    btn.style.cursor = 'default';
                    
                    stopWorkTimer(taskId);
                    timer.textContent = formatTime(data.total_seconds || 0);
                }
                return;
            }

            // Regular user logic remains the same
            // If task is completed, show Completed button
            if (data.is_completed) {
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Completed';
                btn.style.background = '#10b981';
                btn.onclick = (e) => { e.stopPropagation(); };
                btn.style.cursor = 'default';
                
                // Stop timer and show total
                stopWorkTimer(taskId);
                timer.textContent = formatTime(data.total_seconds);
            } else if (data.is_running) {
                // Current user is working - show Working button
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Working';
                btn.style.background = '#3b82f6';
                btn.onclick = (e) => { e.stopPropagation(); pauseWork(taskId, e); };
                btn.style.cursor = 'pointer';
                
                if (data.started_at) {
                    // Start timer if work is running
                    const startTime = new Date(data.started_at);
                    const baseSeconds = data.total_seconds - Math.floor((new Date() - startTime) / 1000);
                    updateWorkTimer(taskId, data.started_at, Math.max(0, baseSeconds));
                }
            } else if (data.is_being_worked_by_other) {
                // Another user is working on this task - show Working button (disabled) for regular users
                const userName = data.working_user_name || 'Another user';
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Working';
                btn.style.background = '#3b82f6';
                btn.title = `Being worked on by ${userName}`;
                btn.onclick = (e) => { 
                    e.stopPropagation(); 
                    showErrorMessage(`This task is currently being worked on by ${userName}. Please wait for them to finish.`);
                };
                btn.style.cursor = 'not-allowed';
                
                // Stop timer and show total
                stopWorkTimer(taskId);
                timer.textContent = formatTime(data.total_seconds || 0);
            } else if (data.is_paused) {
                // Work is paused - show Resume button
                btn.innerHTML = '<i class="fas fa-play"></i> Resume';
                btn.style.background = '#10b981';
                btn.onclick = (e) => { e.stopPropagation(); startWork(taskId, e); };
                btn.style.cursor = 'pointer';
                
                // Show total time if paused
                stopWorkTimer(taskId);
                timer.textContent = formatTime(data.total_seconds);
            } else {
                // No work session - show Start button
                btn.innerHTML = '<i class="fas fa-play"></i> Start';
                btn.style.background = '#10b981';
                btn.onclick = (e) => { e.stopPropagation(); startWork(taskId, e); };
                btn.style.cursor = 'pointer';
                
                // Stop timer and show total
                stopWorkTimer(taskId);
                timer.textContent = formatTime(data.total_seconds);
            }
        }

        function startWork(taskId, event) {
            if (event) event.stopPropagation();
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
                    loadWorkStatusCard(taskId);
                    // Update status badge if status changed
                    if (data.status) {
                        updateStatusBadge(taskId, data.status);
                    }
                    // Update paused tasks if any
                    if (data.paused_tasks && data.paused_tasks.length > 0) {
                        data.paused_tasks.forEach(pausedTaskId => {
                            loadWorkStatus(pausedTaskId);
                            loadWorkStatusCard(pausedTaskId);
                            updateStatusBadge(pausedTaskId, 'on_hold');
                        });
                    }
                    // Update stats cards
                    updateStatsCards();
                    showSuccessMessage(data.message || 'Work started!');
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
            if (event) event.stopPropagation();
            
            // Pause the work
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
                    loadWorkStatusCard(taskId);
                    // Update status badge if status changed
                    if (data.status) {
                        updateStatusBadge(taskId, data.status);
                    }
                    // Update stats cards
                    updateStatsCards();
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

        function updateStatusBadge(taskId, newStatus) {
            const badge = document.getElementById(`statusBadge${taskId}`);
            if (badge) {
                // Remove old status class
                badge.className = 'badge badge-status';
                // Add new status class
                badge.classList.add(newStatus);
                // Update text
                const statusText = newStatus.split('_').map(word => 
                    word.charAt(0).toUpperCase() + word.slice(1)
                ).join(' ');
                badge.textContent = statusText;
            }
        }

        // Filter tasks by stat card click
        function filterByEmployee(employeeId) {
            if (!employeeId) {
                // If no employee selected, redirect to employee tasks page without employee_id
                window.location.href = '{{ route("employee.tasks") }}';
                return;
            }
            
            // Get current filter if any
            const urlParams = new URLSearchParams(window.location.search);
            const currentFilter = urlParams.get('filter') || '';
            
            // Build new URL with employee_id
            let newUrl = '{{ route("employee.tasks") }}?employee_id=' + employeeId;
            if (currentFilter) {
                newUrl += '&filter=' + currentFilter;
            }
            
            // Redirect to new URL
            window.location.href = newUrl;
        }

        function filterTasks(filterType) {
            const currentUrl = new URL(window.location.href);
            const employeeId = currentUrl.searchParams.get('employee_id');
            
            // Remove existing filter but keep employee_id
            currentUrl.searchParams.delete('filter');
            
            // Add new filter if not 'all'
            if (filterType !== 'all') {
                currentUrl.searchParams.set('filter', filterType);
            }
            
            // Ensure employee_id is preserved
            if (employeeId) {
                currentUrl.searchParams.set('employee_id', employeeId);
            }
            
            // Redirect to filtered URL
            window.location.href = currentUrl.toString();
        }

        function updateStatsCards() {
            fetch('/dashboard/stats')
                .then(response => response.json())
                .then(stats => {
                    // Update each stat card
                    const totalEl = document.getElementById('statTotal');
                    const pendingEl = document.getElementById('statPending');
                    const inProgressEl = document.getElementById('statInProgress');
                    const completedEl = document.getElementById('statCompleted');
                    const incompleteEl = document.getElementById('statIncomplete');
                    const onHoldEl = document.getElementById('statOnHold');
                    
                    if (totalEl) totalEl.textContent = stats.total || 0;
                    if (pendingEl) pendingEl.textContent = stats.pending || 0;
                    if (inProgressEl) inProgressEl.textContent = stats.in_progress || 0;
                    if (completedEl) completedEl.textContent = stats.completed || 0;
                    if (incompleteEl) incompleteEl.textContent = stats.incomplete || 0;
                    if (onHoldEl) onHoldEl.textContent = stats.on_hold || 0;
                })
                .catch(error => console.error('Error updating stats:', error));
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

        // Close dropdowns on outside click
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

        // Update critical task count
        function updateCriticalTaskCount() {
            fetch('/critical-tasks/count')
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    const criticalTaskBtn = document.querySelector('a[href="{{ route("critical-tasks.index") }}"]');
                    if (criticalTaskBtn) {
                        let badge = criticalTaskBtn.querySelector('.notification-badge');
                        if (data.count > 0) {
                            if (!badge) {
                                badge = document.createElement('span');
                                badge.className = 'notification-badge';
                                criticalTaskBtn.appendChild(badge);
                            }
                            badge.textContent = data.count > 9 ? '9+' : data.count;
                        } else {
                            if (badge) badge.remove();
                        }
                    }
                })
                .catch(error => {
                    console.error('Critical task count error:', error);
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

        // Update critical task count periodically
        setInterval(updateCriticalTaskCount, 30000); // Every 30 seconds
        setTimeout(updateCriticalTaskCount, 1000);

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
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <script>
        // Pass PHP variables to JavaScript
        var criticalTaskIds = @json($criticalTaskIds ?? []);
        var tasksWithWorkSessions = @json($tasksWithWorkSessions ?? []);
        var isAdmin = {{ auth()->user()->isAdmin() || auth()->user()->isSuperAdmin() ? 'true' : 'false' }};
        var isSuperAdmin = {{ auth()->user()->isSuperAdmin() ? 'true' : 'false' }};
        var tasksBaseUrl = '{{ url("/tasks") }}';
        
        $(document).ready(function() {
            // Initialize DataTables
            var table = $('#tasksTable').DataTable({
                'paging': true,
                'pageLength': 10,
                'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
                'searching': true,
                'ordering': true,
                'info': true,
                'autoWidth': false,
                'responsive': true,
                'order': [], // No default sorting
                'language': {
                    'search': 'Search tasks:',
                    'lengthMenu': 'Show _MENU_ tasks per page',
                    'info': 'Showing _START_ to _END_ of _TOTAL_ tasks',
                    'infoEmpty': 'Showing 0 to 0 of 0 tasks',
                    'infoFiltered': '(filtered from _MAX_ total tasks)',
                    'paginate': {
                        'first': 'First',
                        'last': 'Last',
                        'next': 'Next',
                        'previous': 'Previous'
                    },
                    'emptyTable': 'No tasks available'
                },
                'columnDefs': [
                    {
                        'targets': [8], // Actions column (index 8 after removing checkbox)
                        'orderable': false,
                        'searchable': false
                    },
                    {
                        'targets': [0], // Task column - make it searchable and sortable
                        'orderable': true,
                        'searchable': true
                    }
                ],
                'dom': '<"advanced-table-top"lf>rt<"advanced-table-bottom"ip><"clear">',
                'pagingType': 'simple_numbers',
                'drawCallback': function(settings) {
                    // Click handlers are handled by event delegation below
                }
            });

            // Attach click handlers to rows (for task timeline) - using event delegation
            $('#tasksTable tbody').on('click', 'tr', function() {
                var taskId = $(this).data('task-id');
                if (taskId) {
                    showTaskTimeline(parseInt(taskId));
                }
            });

            // Prevent row click when clicking on action buttons
            $('#tasksTable tbody').on('click', '.task-actions-wrapper, .btn, button, .multi-action', function(e) {
                e.stopPropagation();
            });

            // Checkbox functionality removed - no longer needed in list view

            // Page length selector
            $('#pageLengthSelect').on('change', function() {
                table.page.len(parseInt($(this).val())).draw();
                // Regenerate grid if in grid view
                if (isGridView) {
                    generateCardGridView();
                }
            });

            // Regenerate grid when table is redrawn (search, pagination, etc.)
            table.on('draw', function() {
                if (isGridView) {
                    setTimeout(function() {
                        generateCardGridView();
                    }, 100);
                }
            });
            
            // Listen for search input changes to regenerate grid immediately
            table.on('search.dt', function() {
                if (isGridView) {
                    setTimeout(function() {
                        generateCardGridView();
                    }, 150);
                }
            });
            
            // Also listen for keyup on search input for real-time filtering
            $(document).on('keyup', '.dataTables_filter input', function() {
                if (isGridView) {
                    // DataTables will trigger search automatically, which will trigger draw event
                    // But we add a small delay to ensure smooth experience
                    setTimeout(function() {
                        if (isGridView) {
                            generateCardGridView();
                        }
                    }, 300);
                }
            });
        });

        // Load saved preferences from localStorage
        function loadSavedPreferences() {
            // Load text wrap preference first (before view mode)
            var savedWrap = localStorage.getItem('taskManager_textWrap');
            if (savedWrap === 'enabled') {
                textWrapEnabled = true;
                var table = $('#tasksTable');
                table.addClass('text-wrap-enabled');
                table.find('td, th').css('white-space', 'normal');
                $('#textWrapBtn').addClass('active');
            }
            
            // Load grid/list view preference
            var savedView = localStorage.getItem('taskManager_viewMode');
            if (savedView === 'grid') {
                setTimeout(function() {
                    isGridView = true;
                    toggleGridView();
                }, 500);
            }
        }

        var isGridView = false;
        var textWrapEnabled = false;
        
        // Load preferences on page load (after DataTables is initialized)
        $(document).ready(function() {
            // Wait for DataTables to be fully initialized
            setTimeout(function() {
                loadSavedPreferences();
            }, 300);
        });

        // Export to Excel (CSV format)
        function exportToExcel() {
            var table = $('#tasksTable').DataTable();
            var headers = [];
            var csvContent = '\uFEFF'; // BOM for UTF-8 Excel compatibility
            
            // Get headers (skip checkbox column)
            $('#tasksTable thead th').each(function(index) {
                if (index > 0) { // Skip checkbox column
                    var headerText = $(this).text().trim();
                    headers.push('"' + headerText.replace(/"/g, '""') + '"');
                }
            });
            
            csvContent += headers.join(',') + '\n';
            
            // Get all visible rows (respecting current filters)
            table.rows({search: 'applied'}).every(function() {
                var row = this.node();
                var rowData = [];
                
                // Get all cell data (skip actions column)
                $(row).find('td').each(function(index) {
                    // Skip actions column (last column) for export
                    if (index < $(row).find('td').length - 1) {
                        var cellText = $(this).text().trim().replace(/"/g, '""').replace(/\n/g, ' ').replace(/\r/g, '');
                        // For icon columns, extract tooltip data
                        if ($(this).find('.user-icon-wrapper').length) {
                            var tooltipName = $(this).find('.tooltip-name').text().trim();
                            var tooltipDesignation = $(this).find('.tooltip-designation').text().trim();
                            cellText = tooltipName + (tooltipDesignation ? ' (' + tooltipDesignation + ')' : '');
                        }
                        rowData.push('"' + cellText + '"');
                    }
                });
                
                csvContent += rowData.join(',') + '\n';
            });
            
            // Create and download file
            var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            var link = document.createElement('a');
            var url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', 'tasks_export_' + new Date().toISOString().split('T')[0] + '.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
            
            showSuccessMessage('Tasks exported to Excel (CSV) successfully!');
        }

        // Export to PDF
        function exportToPDF() {
            const { jsPDF } = window.jspdf;
            var doc = new jsPDF('l', 'mm', 'a4'); // landscape orientation
            
            var table = $('#tasksTable').DataTable();
            var headers = [];
            var rows = [];
            
            // Get headers
            $('#tasksTable thead th').each(function(index) {
                headers.push($(this).text().trim());
            });
            
            // Get all visible rows (respecting current filters)
            table.rows({search: 'applied'}).every(function() {
                var row = this.node();
                var rowData = [];
                
                // Get all cell data
                $(row).find('td').each(function(index) {
                    // Skip actions column (last column) for export
                    if (index < $(row).find('td').length - 1) {
                        var cellText = $(this).text().trim().replace(/\n/g, ' ').replace(/\r/g, '');
                        // For icon columns, extract tooltip data or use icon description
                        if ($(this).find('.user-icon-wrapper').length) {
                            var tooltipName = $(this).find('.tooltip-name').text().trim();
                            var tooltipDesignation = $(this).find('.tooltip-designation').text().trim();
                            cellText = tooltipName + (tooltipDesignation ? ' (' + tooltipDesignation + ')' : '');
                        }
                        rowData.push(cellText);
                    }
                });
                
                rows.push(rowData);
            });
            
            // Add title
            doc.setFontSize(18);
            doc.text('Task Manager Dashboard', 14, 15);
            doc.setFontSize(12);
            doc.text('Export Date: ' + new Date().toLocaleDateString(), 14, 22);
            
            // Create table
            doc.autoTable({
                head: [headers],
                body: rows,
                startY: 28,
                styles: {
                    fontSize: 8,
                    cellPadding: 3,
                    overflow: 'linebreak',
                    cellWidth: 'wrap'
                },
                headStyles: {
                    fillColor: [139, 92, 246],
                    textColor: [255, 255, 255],
                    fontStyle: 'bold'
                },
                alternateRowStyles: {
                    fillColor: [245, 247, 250]
                },
                margin: { top: 28 }
            });
            
            // Save PDF
            doc.save('tasks_export_' + new Date().toISOString().split('T')[0] + '.pdf');
            
            showSuccessMessage('Tasks exported to PDF successfully!');
        }

        // Print Preview
        function printPreview() {
            var printWindow = window.open('', '_blank');
            var table = $('#tasksTable').clone();
            
            // Remove action buttons for print
            table.find('.task-actions-wrapper, .multi-action').remove();
            
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Task Manager - Print Preview</title>
                        <style>
                            body { font-family: Arial, sans-serif; padding: 20px; }
                            table { width: 100%; border-collapse: collapse; }
                            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                            th { background-color: #f2f2f2; font-weight: bold; }
                            tr:nth-child(even) { background-color: #f9f9f9; }
                            @media print { body { margin: 0; } }
                        </style>
                    </head>
                    <body>
                        <h2>Task Manager Dashboard</h2>
                        ${table[0].outerHTML}
                    </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();
            setTimeout(function() {
                printWindow.print();
            }, 250);
        }


        // Toggle Grid View (Card-based Layout)
        function toggleGridView() {
            try {
                isGridView = true;
                var table = $('#tasksTable').DataTable();
                var tableWrapper = $('#tableWrapper');
                var gridContainer = $('#gridViewContainer');
                var dataTableWrapper = $('#tasksTable').closest('.dataTables_wrapper');
                
                if (!gridContainer.length) {
                    console.error('gridViewContainer element not found');
                    showErrorMessage('Grid container not found');
                    return;
                }
                
                // Hide table but keep search and length menu visible
                $('#tasksTable').hide();
                $('.advanced-table-bottom').hide(); // Hide pagination
                
                // Keep search input visible in grid view
                $('.advanced-table-top').show();
                $('.dataTables_filter').show();
                $('.dataTables_length').show();
                
                tableWrapper.addClass('grid-view');
                $('#gridViewBtn').addClass('active');
                $('#listViewBtn').show();
                $('#gridViewBtn').hide();
                
                // Generate card grid view
                generateCardGridView();
                
                // Save preference to localStorage
                localStorage.setItem('taskManager_viewMode', 'grid');
                
                // Wait a bit for cards to be generated, then show grid container
                setTimeout(function() {
                    gridContainer.css({
                        'display': 'grid',
                        'visibility': 'visible',
                        'opacity': '1'
                    });
                    
                    var cardCount = gridContainer.find('.task-card').length;
                    console.log('Grid view activated, cards generated:', cardCount);
                    
                    if (cardCount === 0) {
                        console.warn('No cards generated!');
                        showErrorMessage('No tasks found to display in grid view');
                    } else {
                        showSuccessMessage('Switched to Card Grid View (' + cardCount + ' cards)');
                    }
                }, 100);
            } catch (error) {
                console.error('Error in toggleGridView:', error);
                showErrorMessage('Error switching to grid view: ' + error.message);
            }
        }

        // Toggle List View (Table View)
        function toggleListView() {
            try {
                isGridView = false;
                var table = $('#tasksTable').DataTable();
                var tableWrapper = $('#tableWrapper');
                var gridContainer = $('#gridViewContainer');
                var dataTableWrapper = $('#tasksTable').closest('.dataTables_wrapper');
                
                // Hide grid container
                gridContainer.css({
                    'display': 'none',
                    'visibility': 'hidden',
                    'opacity': '0'
                });
                
                // Show table and all controls
                $('#tasksTable').show();
                $('.advanced-table-top, .advanced-table-bottom').show();
                
                tableWrapper.removeClass('grid-view');
                $('#gridViewBtn').removeClass('active');
                $('#listViewBtn').hide();
                $('#gridViewBtn').show();
                
                // Save preference to localStorage
                localStorage.setItem('taskManager_viewMode', 'list');
                
                console.log('List view activated');
                showSuccessMessage('Switched to Table/List View');
            } catch (error) {
                console.error('Error in toggleListView:', error);
                showErrorMessage('Error switching to list view: ' + error.message);
            }
        }

        // Generate Card Grid View
        function generateCardGridView() {
            try {
                var table = $('#tasksTable').DataTable();
                var gridContainer = $('#gridViewContainer');
                
                if (!gridContainer.length) {
                    console.error('gridViewContainer not found');
                    return;
                }
                
                gridContainer.empty();
                
                var rowCount = 0;
                
                table.rows({search: 'applied'}).every(function() {
                    var row = this.node();
                    var taskId = $(row).attr('data-task-id') || $(row).data('task-id');
                    
                    if (!taskId) {
                        // Try to get task ID from work button
                        var workBtnId = $(row).find('.work-start-btn').attr('id');
                        if (workBtnId) {
                            taskId = workBtnId.replace('workBtn', '');
                        }
                    }
                    
                    if (!taskId) {
                        console.warn('Task ID not found for row');
                        return;
                    }
                    
                    rowCount++;
                    
                    // Extract data from table row cells (checkbox column removed, indices shifted)
                    var cells = $(row).find('td');
                    
                    // Task title and description (from first cell, index 0)
                    var taskTitle = $(cells[0]).find('.task-title').text().trim() || 'Untitled Task';
                    var taskDesc = $(cells[0]).find('.task-description').text().trim() || '';
                    
                    // Status (from second cell, index 1)
                    var statusBadge = $(cells[1]).find('.badge-status');
                    var status = statusBadge.text().trim() || 'N/A';
                    var statusClass = statusBadge.attr('class') || '';
                    var statusMatch = statusClass.match(/badge-status\s+(\S+)/);
                    statusClass = statusMatch ? statusMatch[1] : '';
                    
                    // Priority (from third cell, index 2)
                    var priorityBadge = $(cells[2]).find('.badge-priority');
                    var priority = priorityBadge.text().trim() || 'N/A';
                    var priorityClass = priorityBadge.attr('class') || '';
                    var priorityMatch = priorityClass.match(/badge-priority\s+(\S+)/);
                    priorityClass = priorityMatch ? priorityMatch[1] : '';
                    var priorityValue = priorityClass.toLowerCase(); // Get actual priority value
                    
                    // Organization (from fourth cell, index 3)
                    var org = $(cells[3]).text().trim() || 'N/A';
                    
                    // Due Date (from fifth cell, index 4)
                    var dueDate = $(cells[4]).text().trim() || 'No due date';
                    
                    // Assign To (from sixth cell, index 5) - extract from tooltip
                    var assignToIcon = $(cells[5]).find('.user-icon-wrapper');
                    var assignTo = 'Unassigned';
                    if (assignToIcon.length) {
                        var assignToName = assignToIcon.find('.tooltip-name').text().trim();
                        var assignToDept = assignToIcon.find('.tooltip-designation').text().trim();
                        assignTo = assignToName + (assignToDept ? ' (' + assignToDept + ')' : '');
                    }
                    
                    // Assign By (from seventh cell, index 6) - extract from tooltip
                    var assignByIcon = $(cells[6]).find('.user-icon-wrapper');
                    var assignBy = 'N/A';
                    if (assignByIcon.length) {
                        var assignByName = assignByIcon.find('.tooltip-name').text().trim();
                        var assignByRole = assignByIcon.find('.tooltip-designation').text().trim();
                        assignBy = assignByName + (assignByRole ? ' (' + assignByRole + ')' : '');
                    }
                    
                    // Created By (from eighth cell, index 7) - extract from tooltip
                    var createdByIcon = $(cells[7]).find('.user-icon-wrapper');
                    var createdBy = 'N/A';
                    if (createdByIcon.length) {
                        var createdByName = createdByIcon.find('.tooltip-name').text().trim();
                        var createdByRole = createdByIcon.find('.tooltip-designation').text().trim();
                        createdBy = createdByName + (createdByRole ? ' (' + createdByRole + ')' : '');
                    }
                    
                    // Determine which buttons to show
                    var isCritical = (criticalTaskIds && criticalTaskIds.includes(parseInt(taskId))) || priorityValue === 'critical';
                    var taskStatus = statusClass.toLowerCase().replace(/\s+/g, '_'); // Normalize status
                    var showCompleteBtn = taskStatus !== 'completed' || (tasksWithWorkSessions && tasksWithWorkSessions.includes(parseInt(taskId)));
                    var showIncompleteBtn = isAdmin && taskStatus !== 'incomplete';
                    var showAdminBtns = isAdmin;
                    var showSuperAdminBtns = isSuperAdmin;
                    
                    // Build action buttons HTML
                    var actionButtonsHtml = '';
                    actionButtonsHtml += '<div class="task-card-actions" onclick="event.stopPropagation();">';
                    actionButtonsHtml += '<div class="multi-action" id="multiActionCard' + taskId + '">';
                    actionButtonsHtml += '<div class="action-wrapper">';
                    
                    // Critical button (always shown)
                    actionButtonsHtml += '<button class="btn-multi-action critical-action" onclick="event.stopPropagation(); toggleCriticalTask(' + taskId + ', event)" title="' + (isCritical ? 'Remove from Critical' : 'Mark as Critical') + '"></button>';
                    
                    // Complete button (conditional)
                    if (showCompleteBtn) {
                        actionButtonsHtml += '<button class="btn-multi-action complete-action" onclick="event.stopPropagation(); completeTask(' + taskId + ', event)" title="Complete Task"></button>';
                    }
                    
                    // Admin buttons
                    if (showAdminBtns) {
                        // Incomplete button (conditional)
                        if (showIncompleteBtn) {
                            actionButtonsHtml += '<button class="btn-multi-action incomplete-action" onclick="event.stopPropagation(); incompleteTask(' + taskId + ', event)" title="Mark as Incomplete"></button>';
                        }
                        // Assign button
                        actionButtonsHtml += '<button class="btn-multi-action assign-action" onclick="event.stopPropagation(); openAssignModal(' + taskId + ')" title="Assign Task"></button>';
                        // Edit button
                        actionButtonsHtml += '<button class="btn-multi-action edit-action" onclick="event.stopPropagation(); editTask(' + taskId + ')" title="Edit Task"></button>';
                    }
                    
                    // Super Admin buttons
                    if (showSuperAdminBtns) {
                        // Delete button
                        actionButtonsHtml += '<button class="btn-multi-action delete-action" onclick="event.stopPropagation(); event.preventDefault(); confirmDeleteTask(document.querySelector(\'#deleteBtnCard' + taskId + '\').closest(\'form\'));" title="Delete Task"></button>';
                        // Hidden delete form
                        actionButtonsHtml += '<form action="' + tasksBaseUrl + '/' + taskId + '" method="POST" style="display: none;" class="delete-task-form" id="deleteFormCard' + taskId + '">';
                        actionButtonsHtml += '<input type="hidden" name="_token" value="{{ csrf_token() }}">';
                        actionButtonsHtml += '<input type="hidden" name="_method" value="DELETE">';
                        actionButtonsHtml += '<button type="submit" id="deleteBtnCard' + taskId + '"></button>';
                        actionButtonsHtml += '</form>';
                    }
                    
                    // Trigger button - use inline handler
                    actionButtonsHtml += '<button type="button" class="multi-action-trigger" data-task-id="' + taskId + '"></button>';
                    actionButtonsHtml += '</div>';
                    actionButtonsHtml += '</div>';
                    actionButtonsHtml += '</div>';
                    
                    // Build work button HTML for admin/super admin or regular users
                    var workButtonHtml = '';
                    if (isAdmin || isSuperAdmin) {
                        // Admin/Super Admin: Show status text instead of start button
                        var statusText = '';
                        var statusColor = '';
                        if (statusClass === 'completed') {
                            statusText = 'Complete';
                            statusColor = '#10b981';
                        } else if (statusClass === 'in_progress') {
                            statusText = 'Running Working';
                            statusColor = '#3b82f6';
                        } else if (statusClass === 'pending') {
                            statusText = 'Pending';
                            statusColor = '#f59e0b';
                        } else if (statusClass === 'on_hold') {
                            statusText = 'On Hold';
                            statusColor = '#f59e0b';
                        } else {
                            statusText = status.charAt(0).toUpperCase() + status.slice(1).replace(/_/g, ' ');
                            statusColor = '#94a3b8';
                        }
                        workButtonHtml = '<span class="btn btn-sm" id="workBtnCard' + taskId + '" style="background: ' + statusColor + '; color: white; font-size: 11px; padding: 4px 8px; border-radius: 6px; border: none; cursor: default;">' + statusText + '</span>';
                    } else {
                        // Regular user: Show start button
                        workButtonHtml = '<button class="btn btn-sm work-start-btn" id="workBtnCard' + taskId + '" onclick="startWork(' + taskId + ', event)" style="background: #10b981; color: white; font-size: 11px; padding: 4px 8px; border-radius: 6px;" title="Start Work"><i class="fas fa-play"></i> Start</button>';
                    }
                    
                    // Create card HTML
                    var cardHtml = `
                        <div class="task-card" data-task-id="${taskId}">
                            <div class="task-card-content">
                                <div class="task-card-header">
                                    <div class="task-card-avatar">${taskTitle.charAt(0).toUpperCase()}</div>
                                    <div class="task-card-info">
                                        <h4 class="task-card-title">${taskTitle}</h4>
                                        ${taskDesc ? '<p class="task-card-subtitle">' + taskDesc.substring(0, 50) + (taskDesc.length > 50 ? '...' : '') + '</p>' : ''}
                                    </div>
                                </div>
                                <div class="task-card-body">
                                    <div class="task-card-row">
                                        <span class="task-card-label">Status:</span>
                                        <span class="badge badge-status ${statusClass}" id="statusBadge${taskId}">${status}</span>
                                    </div>
                                    <div class="task-card-row">
                                        <span class="task-card-label">Priority:</span>
                                        <span class="badge badge-priority ${priorityClass}">${priority}</span>
                                    </div>
                                    <div class="task-card-row">
                                        <span class="task-card-label">Organization:</span>
                                        <span class="task-card-value">${org}</span>
                                    </div>
                                    <div class="task-card-row">
                                        <span class="task-card-label">Due Date:</span>
                                        <span class="task-card-value">${dueDate}</span>
                                    </div>
                                    <div class="task-card-row">
                                        <span class="task-card-label">Assign To:</span>
                                        <span class="task-card-value">${assignTo}</span>
                                    </div>
                                </div>
                                <div class="task-card-footer">
                                    <div class="task-card-work-timer">
                                        ${workButtonHtml}
                                        <span class="work-timer" id="workTimerCard${taskId}" style="font-size: 11px; color: #475569; font-weight: 600; min-width: 70px;">00:00:00</span>
                                    </div>
                                    ${actionButtonsHtml}
                                    <span class="task-card-status-tag badge-status ${statusClass}">${status}</span>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    var card = $(cardHtml);
                    
                    // Make card clickable (except buttons and action areas)
                    card.on('click', function(e) {
                        // Don't trigger timeline if clicking on buttons, actions, or forms
                        if ($(e.target).is('button') || 
                            $(e.target).closest('button').length ||
                            $(e.target).closest('.task-card-actions').length ||
                            $(e.target).closest('.multi-action').length ||
                            $(e.target).closest('form').length ||
                            $(e.target).closest('.task-card-work-timer').length) {
                            return;
                        }
                        showTaskTimeline(parseInt(taskId));
                    });
                    
                    // Ensure all buttons stop propagation
                    card.find('button').on('click', function(e) {
                        e.stopPropagation();
                    });
                    
                    // Add click handler for multi-action trigger button
                    card.find('.multi-action-trigger').on('click', function(e) {
                        e.stopPropagation();
                        var cardTaskId = $(this).closest('.task-card').data('task-id');
                        if (cardTaskId) {
                            toggleMultiActionCard(cardTaskId, e);
                        }
                    });
                    
                    gridContainer.append(card);
                });
                
                console.log('Generated ' + rowCount + ' cards');
                
                // Load work status for all cards after all cards are appended
                setTimeout(function() {
                    table.rows({search: 'applied'}).every(function() {
                        var row = this.node();
                        var taskId = $(row).attr('data-task-id') || $(row).data('task-id');
                        if (!taskId) {
                            // Try to get from work button or work status span (for admin/super admin)
                            var workBtnId = $(row).find('.work-start-btn').attr('id') || $(row).find('[id^="workBtn"]').attr('id');
                            if (workBtnId) {
                                taskId = workBtnId.replace('workBtn', '').replace('workBtnCard', '');
                            }
                        }
                        if (taskId) {
                            loadWorkStatusCard(taskId);
                        }
                    });
                }, 100);
            } catch (error) {
                console.error('Error generating card grid view:', error);
                showErrorMessage('Error loading grid view: ' + error.message);
            }
        }

        // Open Column Settings

        // Font Size Controls
        function decreaseFontSize() {
            var currentSize = parseFloat($('.task-table').css('font-size'));
            if (currentSize > 10) {
                $('.task-table').css('font-size', (currentSize - 1) + 'px');
                localStorage.setItem('tableFontSize', (currentSize - 1) + 'px');
            }
        }

        function increaseFontSize() {
            var currentSize = parseFloat($('.task-table').css('font-size'));
            if (currentSize < 20) {
                $('.task-table').css('font-size', (currentSize + 1) + 'px');
                localStorage.setItem('tableFontSize', (currentSize + 1) + 'px');
            }
        }

        // Toggle Text Wrap
        function toggleTextWrap() {
            textWrapEnabled = !textWrapEnabled;
            var table = $('#tasksTable');

            if (textWrapEnabled) {
                table.addClass('text-wrap-enabled');
                table.find('td, th').css('white-space', 'normal');
                $('#textWrapBtn').addClass('active');
                localStorage.setItem('taskManager_textWrap', 'enabled');
                showSuccessMessage('Text wrap enabled');
            } else {
                table.removeClass('text-wrap-enabled');
                table.find('td, th').css('white-space', 'nowrap');
                $('#textWrapBtn').removeClass('active');
                localStorage.setItem('taskManager_textWrap', 'disabled');
                showSuccessMessage('Text wrap disabled');
            }
        }

        // Load saved font size
        $(document).ready(function() {
            var savedFontSize = localStorage.getItem('tableFontSize');
            if (savedFontSize) {
                $('.task-table').css('font-size', savedFontSize);
            }
        });

        // Close modals when clicking outside
        $(window).on('click', function(event) {
            if ($(event.target).hasClass('modal')) {
                $(event.target).css('display', 'none');
            }
        });
    </script>
</body>
</html>

