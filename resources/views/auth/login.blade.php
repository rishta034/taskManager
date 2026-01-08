<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - Task Manager</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
</head>
<body>
    <canvas id="canvas"></canvas>
    
    <div class="login-container">
        <div class="logo">
            <i class="fas fa-tasks"></i>
            <span>TaskManager</span>
        </div>
        
        <h1>Welcome Back</h1>
        <p>Sign in to your account</p>

        @if ($errors->any())
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                {{ $errors->first() }}
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
            </div>
        @endif

        <form id="loginForm" method="POST" action="{{ route('login') }}">
            @csrf

            <div class="role-selector">
                <label class="form-group label" style="margin-bottom: 10px; display: block;color: #64c8ff;font-size: 13px; 
                font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Select Your Role *</label>
                <div class="role-tabs">
                    <label class="role-tab {{ old('role', 'user') === 'user' ? 'active' : '' }}">
                        <input type="radio" name="role" value="user" {{ old('role', 'user') === 'user' ? 'checked' : '' }} required>
                        User
                    </label>
                    <label class="role-tab {{ old('role') === 'admin' ? 'active' : '' }}">
                        <input type="radio" name="role" value="admin" {{ old('role') === 'admin' ? 'checked' : '' }} required>
                        Admin
                    </label>
                    <label class="role-tab {{ old('role') === 'super_admin' ? 'active' : '' }}">
                        <input type="radio" name="role" value="super_admin" {{ old('role') === 'super_admin' ? 'checked' : '' }} required>
                        Super Admin
                    </label>
                </div>
                @error('role')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="@error('email') border-red-500 @enderror" 
                    value="{{ old('email') }}" 
                    required 
                    autofocus
                    placeholder="Enter your email"
                >
                @error('email')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="@error('password') border-red-500 @enderror" 
                    required
                    placeholder="Enter your password"
                >
                @error('password')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-checkbox">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Remember me</label>
            </div>

            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt"></i>
                Sign In
            </button>
        </form>

        <div class="divider">
            <span>OR</span>
        </div>

        <form id="googleLoginForm" method="GET" action="{{ route('google.login') }}" style="display: none;">
            <input type="hidden" name="role" id="googleRole" value="">
        </form>
        <a href="#" id="googleLoginBtn" class="btn-google">
            <i class="fab fa-google"></i>
            Continue with Google
        </a>
    </div>

    <div id="successMsg" class="success-message">Login Successful!</div>

    <script src="{{ asset('js/auth.js') }}"></script>
</body>
</html>
