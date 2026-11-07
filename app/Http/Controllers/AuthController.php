<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // Cutoff dan namespace unik untuk login ini, terpisah di setiap device/session.
            $request->session()->put([
                'admin_logged_in_at' => now()->toDateTimeString(),
                'admin_notification_session' => (string) Str::uuid(),
            ]);

            $user = Auth::user();
            if ($user->status === 'admin') {
                return redirect()->route('admin.dashboard');
            }
            return redirect()->route('admin.selforder');
        }

        return back()->withErrors(['Login gagal. Periksa username dan password Anda.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
