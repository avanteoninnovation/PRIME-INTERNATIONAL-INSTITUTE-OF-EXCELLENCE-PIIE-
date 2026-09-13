<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');

        // login() below is a full custom override of AuthenticatesUsers's
        // default method, so Laravel's usual ThrottlesLogins lockout never
        // engages — this route had no brute-force protection at all
        // (unlike applicant/login, which already carries a throttle
        // middleware). 5 attempts/minute per IP+session, same limiter
        // Laravel's own scaffolding uses by default.
        $this->middleware('throttle:5,1')->only('login');
    }

    public function login(Request $request)
    {
        $input = $request->all();

        $this->validate($request, [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (school_status_check($input['email']) == 1 || user_role_check($input['email']) == 1 || user_role_check($input['email']) == 2) {

            if (auth()->attempt(['email' => $input['email'], 'password' => $input['password']])) {
                if (auth()->user()->role_id == 1) {

                    session(['superadmin_login' => 1]);
                    return redirect()->route('superadmin.dashboard');

                } else {
                    if (auth()->user()->role_id == 2) {

                        session(['admin_login' => 2]);
                        return redirect()->route('admin.dashboard');

                    } else if (auth()->user()->role_id == 3) {

                        session(['teacher_login' => 3]);
                        return redirect()->route('teacher.dashboard');

                    } else if (auth()->user()->role_id == 4) {

                        session(['accountant_login' => 4]);
                        return redirect()->route('accountant.dashboard');

                    } elseif ((auth()->user()->role_id == 5)) {

                        session(['librarian_login' => 5]);
                        return redirect()->route('librarian.dashboard');

                    } elseif ((auth()->user()->role_id == 6)) {

                        session(['parent_login' => 6]);
                        return redirect()->route('parent.dashboard');

                    } else if (auth()->user()->role_id == 7) {

                        session(['student_login' => 7]);
                        return redirect()->route('student.dashboard');

                    } else if (auth()->user()->role_id == 8) {

                        session(['driver_login' => 8]);
                        return redirect()->route('driver.dashboard');

                    } else if (auth()->user()->role_id == 9) {

                        // role_id 9 is Registrar (RegistrarMiddleware), not
                        // "alumni" — that route doesn't exist, and never
                        // did; nothing gated 'alumni.dashboard' before this
                        // fix, so logging in as a Registrar 500'd right
                        // after authenticating. No Registrar-specific
                        // dashboard exists yet either, so this lands them
                        // on Admin's for now until one is built.
                        session(['registrar_login' => 9]);
                        return redirect()->route('admin.dashboard');

                    } else if (auth()->user()->role_id == 10) {
                        session(['warden_login' => 10]);
                        return redirect()->route('warden.dashboard');
                    } else if (auth()->user()->role_id == 15) {

                        // role_id 15 is HR Manager (HrManagerMiddleware).
                        // No branch existed for it at all, so it fell
                        // through to the landing page after a successful
                        // login. No HR-specific dashboard exists, but the
                        // 'hr_manager' middleware already gates a real
                        // leave-management page (admin.leave.index), so
                        // send them there instead of the marketing site.
                        session(['hr_manager_login' => 15]);
                        return redirect()->route('admin.leave.index');

                    } else {
                        return redirect()->route('landingPage');
                    }
                }
            } else {
                return redirect()->route('login')
                    ->with('error', 'Email-Address And Password Are Wrong.');
            }
        } else {
            return redirect()->route('login')
                ->with('error', 'Your school is yet to be authorized to this service!');
        }
    }
}
