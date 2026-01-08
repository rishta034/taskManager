<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Two-Factor Authentication - Task Manager</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/2fa.css') }}">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
</head>
<body>
    <canvas id="canvas"></canvas>
    
    <div class="auth-container">
        <div class="auth-header">
            <div class="logo">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h1>Two-Factor Authentication</h1>
            <p>Enter the code from your authentication app</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                {{ $errors->first() }}
            </div>
        @endif

        @if($showQRCode)
        <div class="instructions">
            <h3><i class="fas fa-info-circle"></i> Setup Instructions</h3>
            <ol>
                <li>Open your authentication app (Google Authenticator, Authy, etc.)</li>
                <li>Scan the QR code below or enter the secret key manually</li>
                <li>Enter the 6-digit code from your app</li>
            </ol>
        </div>

        <div class="qr-section">
            <div class="qr-code">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($qrCodeUrl) }}" alt="QR Code">
            </div>
            <div class="secret-key">
                <strong>Secret Key:</strong><br>
                {{ $secret }}
            </div>
        </div>
        @else
        <div class="instructions" style="background: rgba(0, 255, 136, 0.1); border-left-color: #00ff88;">
            <h3 style="color: #00ff88;"><i class="fas fa-check-circle"></i> 2FA Already Configured</h3>
            <p>Enter the 6-digit code from your authentication app to continue.</p>
        </div>
        @endif

        <form method="POST" action="{{ route('2fa.verify.post') }}">
            @csrf

            <div class="form-group">
                <label for="code">Authentication Code</label>
                <input 
                    type="text" 
                    id="code" 
                    name="code" 
                    class="@error('code') border-red-500 @enderror" 
                    required 
                    autofocus
                    maxlength="6"
                    pattern="[0-9]{6}"
                    placeholder="000000"
                    autocomplete="off"
                >
                @error('code')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-checkbox">
                <input type="checkbox" name="remember" id="remember">
                <label for="remember">Remember this device</label>
            </div>

            <button type="submit" class="btn">
                <i class="fas fa-check"></i>
                Verify Code
            </button>
        </form>

        <div class="back-link">
            <a href="{{ route('login') }}">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        </div>
    </div>

    <script src="{{ asset('js/2fa.js') }}"></script>
</body>
</html>
