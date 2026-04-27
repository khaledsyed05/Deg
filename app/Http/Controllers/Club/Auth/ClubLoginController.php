<?php

namespace App\Http\Controllers\Club\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ClubLoginController extends Controller
{
    public function showLogin(): Response|RedirectResponse
    {
        if (Auth::check() && Auth::user()->hasRole('club_manager')) {
            return redirect()->route('club.dashboard');
        }

        return Inertia::render('Club/Auth/Login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_number';

        $credentials = [
            $field => $data['login'],
            'password' => $data['password'],
        ];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['login' => 'بيانات الدخول غير صحيحة.'])
                ->onlyInput('login');
        }

        $user = Auth::user();

        if (! $user->hasRole('club_manager')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors(['login' => 'هذا الحساب لا يملك صلاحية الوصول للوحة النادي.'])
                ->onlyInput('login');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('club.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('club.login');
    }

    public function showForgotPassword(): Response
    {
        return Inertia::render('Club/Auth/ForgotPassword');
    }

    public function sendResetOtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone_number' => [
                'required',
                'string',
                Rule::exists('users', 'phone_number'),
            ],
        ]);

        $otp = (string) random_int(100000, 999999);

        cache()->put(
            "club_password_reset:{$data['phone_number']}",
            $otp,
            now()->addMinutes(10)
        );

        // TODO: dispatch WhatsApp OTP send job
        // dispatch(new SendPasswordResetOtp($data['phone_number'], $otp));

        return redirect()
            ->route('club.reset-password', ['phone' => $data['phone_number']])
            ->with('flash_key', 'clubOtpSent')
            ->with('flash_type', 'success');
    }

    public function showResetPassword(Request $request): Response
    {
        return Inertia::render('Club/Auth/ResetPassword', [
            'phone' => (string) $request->query('phone', ''),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone_number' => ['required', 'string', Rule::exists('users', 'phone_number')],
            'otp' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $cached = cache()->get("club_password_reset:{$data['phone_number']}");

        if (! $cached || ! hash_equals((string) $cached, (string) $data['otp'])) {
            return back()->withErrors(['otp' => 'رمز التحقق غير صحيح أو منتهي الصلاحية.']);
        }

        $user = User::where('phone_number', $data['phone_number'])->first();

        if (! $user || ! $user->hasRole('club_manager')) {
            return back()->withErrors(['phone_number' => 'لا يوجد حساب نادٍ مرتبط بهذا الرقم.']);
        }

        $user->update(['password' => Hash::make($data['password'])]);

        cache()->forget("club_password_reset:{$data['phone_number']}");

        return redirect()
            ->route('club.login')
            ->with('flash_key', 'clubPasswordReset')
            ->with('flash_type', 'success');
    }
}
