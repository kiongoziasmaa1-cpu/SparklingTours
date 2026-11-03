<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function loginForm()
    {
        return view('auth.login');
    }

    public function login(Request $r)
    {
        $d = $r->validate(['email' => 'required|email', 'password' => 'required|string|max:500']);
        $u = User::where('email', $d['email'])->first();
        if (! $u || ! $u->active || ! Hash::check($d['password'], $u->password)) {
            throw ValidationException::withMessages(['email' => 'The credentials could not be verified.']);
        }
        $r->session()->regenerate();
        Auth::login($u);
        $r->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect('/');
    }

    public function forgotForm()
    {
        return view('auth.forgot');
    }

    public function forgot(Request $r)
    {
        $r->validate(['email' => 'required|email']);
        try {
            Password::sendResetLink($r->only('email'));
        } catch (\Throwable $e) {
            report(new \RuntimeException('Password-reset mail unavailable.'));
        }

return back()->with('status', 'If the account exists, reset instructions have been sent.');
    }

    public function resetForm(Request $r, string $token)
    {
        return view('auth.reset', ['token' => $token, 'email' => $r->query('email')]);
    }

    public function reset(Request $r)
    {
        $r->validate(['token' => 'required', 'email' => 'required|email', 'password' => 'required|string|min:12|max:200|confirmed']);
        $status = Password::reset($r->only('email', 'password', 'password_confirmation', 'token'), function (User $u, string $password) {
            $u->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            DB::table('sessions')->where('user_id', $u->id)->delete();
        });
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

return redirect()->route('login')->with('status','Password reset. Please sign in.');
    }
}
