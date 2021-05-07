<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SecurityService\SecurityService;
use App\User;
use Carbon\Carbon;
use Cookie;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('install')->except('logout');
        $this->middleware('guest')->except('logout');
        $this->redirectTo = route('home');
    }

    /**
     * @param Request $request
     * @param $user
     */
    public function authenticated(Request $request, $user)
    {
        $token = $request->cookie('accounting_token');
        $service = new SecurityService();
        if (!empty($token) && $token != $user->id) {
            $previousUser = User::find($token);
            $service->anotherUserLogin($previousUser, $user);
        } else {
            $token = $user->id;
            Cookie::queue('accounting_token', $token, Carbon::tomorrow()->setTime(0, 0, 0)->diffInMinutes(Carbon::now()));
        }

        if ($user->is_not_working) {
            $service->firedLogin($user);
        }
    }
}
