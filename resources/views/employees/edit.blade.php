<!DOCTYPE html>
<html lang="en" data-theme="{{ auth()->user()->theme ?? 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Edit Employee - Task Manager</title>
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
                <h1>Edit Employee</h1>
                <div class="header-actions">
                    <a href="{{ route('employees.index') }}" class="btn" style="background: #f1f5f9; color: #475569;">
                        <i class="fas fa-arrow-left"></i>
                        Back to List
                    </a>
                </div>
            </div>

            @if($errors->any())
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div class="task-section">
                <form action="{{ route('employees.update', $employee) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <h3 style="margin-bottom: 20px; color: var(--text-primary);">Account Information</h3>
                    
                    <div class="form-group">
                        <label class="form-label">First Name *</label>
                        <input type="text" name="first_name" class="form-input" value="{{ old('first_name', $employee->first_name) }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Last Name *</label>
                        <input type="text" name="last_name" class="form-input" value="{{ old('last_name', $employee->last_name) }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" class="form-input" value="{{ old('email', $employee->user->email) }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password (Leave blank to keep current password)</label>
                        <div class="password-input-wrapper">
                            <input type="password" name="password" id="password" class="form-input">
                            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                <i class="fas fa-eye" id="password-icon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-input">
                            <button type="button" class="password-toggle" onclick="togglePassword('password_confirmation')">
                                <i class="fas fa-eye" id="password_confirmation-icon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Role *</label>
                        <select name="role" class="form-select" required>
                            <option value="user" {{ old('role', $employee->user->role) === 'user' ? 'selected' : '' }}>User</option>
                            <option value="admin" {{ old('role', $employee->user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="super_admin" {{ old('role', $employee->user->role) === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                        </select>
                    </div>

                    <h3 style="margin: 30px 0 20px 0; color: var(--text-primary);">Employee Details</h3>

                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-input" value="{{ old('phone', $employee->phone) }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-textarea" rows="3">{{ old('address', $employee->address) }}</textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select">
                            <option value="">Select Department</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" {{ old('department_id', $employee->department_id) == $department->id ? 'selected' : '' }}>
                                    {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-select" required>
                            <option value="active" {{ old('status', $employee->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $employee->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="terminated" {{ old('status', $employee->status) === 'terminated' ? 'selected' : '' }}>Terminated</option>
                        </select>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('employees.index') }}" class="btn" style="background: #f1f5f9; color: #475569;">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Update Employee
                        </button>
                    </div>
                </form>
            </div>
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

        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const passwordIcon = document.getElementById(fieldId + '-icon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        }

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

