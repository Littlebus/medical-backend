<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
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
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    public function login(Request $request) {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials, true)) {
            // Authentication passed...
            return response()->json([
                'success' => true,
                'data' => Auth::user()
            ]);
        }
        return response()->json([
            'success' => false,
            'data' => null
        ]);
    }

    public function logout(Request $request) {
        Auth::logout();
        return response()->json([
            'success' => true,
            'data' => null
        ]);
    }

    public function getSelf(Request $request) {
        return response()->json([
            'success' => true,
            'data' => Auth::user()
        ]);
    }
}
