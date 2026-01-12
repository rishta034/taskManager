<!DOCTYPE html>
<html lang="en" data-theme="{{ auth()->user()->theme ?? 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Employees - Task Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <a href="{{ route('employees.index') }}" class="menu-item {{ request()->routeIs('employees.*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i>
                    <span>Employees</span>
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
                <h1>Employees</h1>
                <div class="header-actions">
                    <a href="{{ route('employees.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Add New Employee
                    </a>
                </div>
            </div>


            @if($employees->count() > 0)
            <div class="task-section">
                <table class="task-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Department</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employees as $employee)
                        <tr>
                            <td>
                                <div class="task-title">{{ $employee->full_name }}</div>
                            </td>
                            <td>{{ $employee->user->email }}</td>
                            <td>{{ $employee->phone ?? 'N/A' }}</td>
                            <td>{{ $employee->department->name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge badge-priority {{ $employee->user->role }}">
                                    {{ ucfirst(str_replace('_', ' ', $employee->user->role)) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-status {{ $employee->status }}">
                                    {{ ucfirst($employee->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="task-actions">
                                    <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm" style="background: #f1f5f9; color: #475569;">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('employees.destroy', $employee) }}" method="POST" style="display: inline;" class="delete-employee-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="event.preventDefault(); confirmDeleteEmployee(this);">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                @if($employees->hasPages())
                <div style="margin-top: 20px; display: flex; justify-content: center;">
                    {{ $employees->links() }}
                </div>
                @endif
            </div>
            @else
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <h3>No employees yet</h3>
                <p>Get started by adding your first employee!</p>
            </div>
            @endif
        </main>
    </div>

    <script>
        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            const userInfo = document.querySelector('.user-info');
            dropdown.classList.toggle('active');
            userInfo.classList.toggle('active');
        }

        document.addEventListener('click', function(event) {
            const userMenu = document.querySelector('.user-menu');
            const dropdown = document.getElementById('userDropdown');
            if (userMenu && !userMenu.contains(event.target)) {
                dropdown.classList.remove('active');
                document.querySelector('.user-info').classList.remove('active');
            }
        });

        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }

        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-theme', newTheme);
            const themeSwitch = document.getElementById('themeSwitch');
            themeSwitch.classList.toggle('active');
            
            const themeIcon = document.querySelector('.theme-toggle-label i');
            themeIcon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            
            const themeLabel = document.querySelector('.theme-toggle-label span');
            themeLabel.textContent = newTheme === 'dark' ? 'Light Mode' : 'Dark Mode';
            
            fetch('{{ route("settings.theme") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ theme: newTheme })
            }).catch(error => console.error('Error updating theme:', error));
        }

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

        // SweetAlert2 Functions
        function confirmDeleteEmployee(button) {
            const form = button.closest('form');
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to delete this employee?",
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>

