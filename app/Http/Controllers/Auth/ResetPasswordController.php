<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Support\Roles\SystemRole;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    public function showResetForm(Request $request)
    {
        $user = \App\Models\User::where('email', $request->query('email'))->first();
        $token = $request->route()->parameter('token');

        if ($user && (int) $user->role_id === SystemRole::GENERIC_STAFF
            && Password::broker('users')->tokenExists($user, $token)) {
            return view('auth.passwords.staff-setup', [
                'token' => $token,
                'email' => $user->email,
            ]);
        }

        return view('auth.passwords.reset')->with([
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    protected function resetPassword($user, $password)
    {
        $user->forceFill([
            'password' => Hash::make($password),
            'remember_token' => Str::random(60),
        ]);

        if ((int) $user->role_id === SystemRole::GENERIC_STAFF) {
            $user->force_password_change = false;
        }

        $user->save();
        event(new PasswordReset($user));
        $this->guard()->login($user);
    }

    public function redirectPath()
    {
        return (int) auth()->user()?->role_id === SystemRole::GENERIC_STAFF
            ? route('staff.dashboard')
            : $this->redirectTo;
    }

    protected function validationErrorMessages()
    {
        return [
            'token.required' => 'This password setup link is incomplete. Ask your administrator to send a new link.',
            'email.required' => 'Enter the email address associated with this account.',
            'email.email' => 'Enter a valid email address.',
            'password.required' => 'Choose a new password.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];
    }
}
