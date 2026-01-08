<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use PragmaRX\Google2FA\Google2FA;
use Exception;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'role' => 'required|in:super_admin,admin,user',
        ]);

        $user = User::where('email', $request->email)->first();
        
        // Check if user exists
        if (!$user) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->withInput($request->only('email', 'role'));
        }
        
        // Check if user has Google ID - if yes, they must use Google auth only
        if (!empty($user->google_id)) {
            return back()->withErrors([
                'email' => 'This account uses Google authentication only. Please sign in with Google.',
            ])->withInput($request->only('email', 'role'));
        }
        
        // Check if user has a password (not Google OAuth only)
        if (is_null($user->password)) {
            return back()->withErrors([
                'email' => 'This account uses Google authentication. Please sign in with Google.',
            ])->withInput($request->only('email', 'role'));
        }
        
        // Validate role matches
        if ($user->role !== $request->role) {
            return back()->withErrors([
                'role' => 'The selected role does not match your account role.',
            ])->withInput($request->only('email', 'role'));
        }
        
        $credentials = $request->only('email', 'password');
        $remember = $request->filled('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            
            $user = Auth::user();
            
            // Generate 2FA secret if not exists
            if (empty($user->two_factor_secret)) {
                $google2fa = new Google2FA();
                $user->two_factor_secret = $google2fa->generateSecretKey();
                $user->two_factor_recovery_codes = json_encode([
                    bin2hex(random_bytes(4)),
                    bin2hex(random_bytes(4)),
                    bin2hex(random_bytes(4)),
                    bin2hex(random_bytes(4)),
                    bin2hex(random_bytes(4)),
                ]);
                $user->save();
            }
            
            // Store user ID in session for 2FA verification
            session(['2fa_user_id' => $user->id]);
            Auth::logout();
            
            // Redirect to 2FA verification page
            return redirect()->route('2fa.verify');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->withInput($request->only('email', 'role'));
    }

    public function redirectToGoogle(Request $request)
    {
        $request->validate([
            'role' => 'required|in:super_admin,admin,user',
        ]);
        
        // Check if user exists with this email and role, and verify they use Google auth
        $email = $request->input('email');
        if ($email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                // Validate role matches
                if ($user->role !== $request->role) {
                    return redirect()->route('login')->withErrors([
                        'role' => 'The selected role does not match your account role.',
                    ]);
                }
                
                // Check if user has Google ID (uses Google auth)
                if (empty($user->google_id)) {
                    return redirect()->route('login')->withErrors([
                        'email' => 'This account uses email/password authentication. Please sign in with email and password.',
                    ])->withInput($request->only('email', 'role'));
                }
            }
        }
        
        // Store selected role in session
        session(['login_role' => $request->role]);
        
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            $user = User::where('google_id', $googleUser->id)
                ->orWhere('email', $googleUser->email)
                ->first();

            // Get role from session
            $selectedRole = session('login_role');
            session()->forget('login_role');
            
            if ($user) {
                // Validate role matches
                if ($user->role !== $selectedRole) {
                    return redirect()->route('login')->withErrors([
                        'role' => 'The selected role does not match your account role.',
                    ]);
                }
                
                // If user exists but doesn't have Google ID, check if they have password
                // If they have password, they should use email/password login, not Google
                if (empty($user->google_id) && !empty($user->password)) {
                    return redirect()->route('login')->withErrors([
                        'email' => 'This account uses email/password authentication. Please sign in with email and password.',
                    ]);
                }
                
                // Set Google ID if not already set (for existing users migrating to Google)
                if (empty($user->google_id)) {
                    $user->google_id = $googleUser->id;
                    $user->avatar = $googleUser->avatar;
                    // Remove password to enforce Google-only login
                    $user->password = null;
                    $user->save();
                }
            } else {
                // Create new user with selected role
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                    'password' => null, // No password for Google OAuth users
                    'email_verified_at' => now(),
                    'role' => $selectedRole,
                ]);
            }

            // Generate 2FA secret if not exists for Google OAuth users too
            if (empty($user->two_factor_secret)) {
                $google2fa = new Google2FA();
                $user->two_factor_secret = $google2fa->generateSecretKey();
                $user->two_factor_recovery_codes = json_encode([
                    bin2hex(random_bytes(4)),
                    bin2hex(random_bytes(4)),
                    bin2hex(random_bytes(4)),
                    bin2hex(random_bytes(4)),
                    bin2hex(random_bytes(4)),
                ]);
                $user->save();
            }
            
            // Store user ID in session for 2FA verification
            session(['2fa_user_id' => $user->id]);
            
            // Redirect to 2FA verification page
            return redirect()->route('2fa.verify');
        } catch (Exception $e) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google authentication failed. Please try again.',
            ]);
        }
    }

    public function show2FAForm()
    {
        if (!session('2fa_user_id')) {
            return redirect()->route('login');
        }
        
        $user = User::find(session('2fa_user_id'));
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Check if user has verified 2FA before
        $showQRCode = !$user->google_auth_verified;
        
        $qrCodeUrl = null;
        if ($showQRCode) {
            $google2fa = new Google2FA();
            $qrCodeUrl = $google2fa->getQRCodeUrl(
                config('app.name'),
                $user->email,
                $user->two_factor_secret
            );
        }
        
        return view('auth.2fa', [
            'qrCodeUrl' => $qrCodeUrl,
            'secret' => $user->two_factor_secret,
            'showQRCode' => $showQRCode,
        ]);
    }

    public function verify2FA(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        if (!session('2fa_user_id')) {
            return redirect()->route('login');
        }

        $user = User::find(session('2fa_user_id'));
        if (!$user) {
            return redirect()->route('login');
        }

        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey($user->two_factor_secret, $request->code);

        if ($valid) {
            // Mark 2FA as verified for first time
            if (!$user->google_auth_verified) {
                $user->google_auth_verified = true;
                $user->save();
            }
            
            Auth::login($user, $request->filled('remember'));
            session()->forget('2fa_user_id');
            $request->session()->regenerate();
            
            return $this->redirectToRole($user);
        }

        return back()->withErrors([
            'code' => 'Invalid authentication code. Please try again.',
        ]);
    }

    protected function redirectToRole($user)
    {
        if ($user->isSuperAdmin()) {
            return redirect()->route('super-admin.dashboard');
        } elseif ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        } else {
            return redirect()->route('dashboard');
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login');
    }

    public function updateTheme(Request $request)
    {
        $request->validate([
            'theme' => 'required|in:light,dark',
        ]);

        $user = Auth::user();
        $user->theme = $request->theme;
        $user->save();

        return response()->json(['success' => true, 'theme' => $user->theme]);
    }
}

