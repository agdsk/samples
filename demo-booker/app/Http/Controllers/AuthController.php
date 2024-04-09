<?php namespace App\Http\Controllers;

use App\Models\PasswordResetToken;
use App\Models\User;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    protected $auth;

    public function login()
    {
        return view('auth.login');
    }

    public function postLogin(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email', 'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');

        $User = User::where('email', $request->input('email'))->first();

        if (!$User) {
            return redirect(action('AuthController@login'))->withInput($request->only('email'))->withErrors(['email' => 'These credentials do not match our records.',]);
        }

        if ($User->status <= 0) {
            return redirect(action('AuthController@login'))->withInput($request->only('email', 'remember'))->withErrors(['email' => 'This user is no longer active',]);
        }

        if ($this->auth->attempt($credentials, $request->has('remember'))) {
            return redirect()->intended('/');
        }

        return redirect(action('AuthController@login'))->withInput($request->only('email', 'remember'))->withErrors(['email' => 'These credentials do not match our records.',]);
    }

    public function token(Request $request)
    {
        $PasswordResetToken = PasswordResetToken::where('token', $request->route('token'))->first();

        if (!$PasswordResetToken) {
            return redirect(action('ForgotPasswordController@forgot'))->with('message', 'The link you clicked is no longer valid. Please enter your email address again.');
        }

        $User = User::where('email', $PasswordResetToken->email)->first();

        if (!$PasswordResetToken) {
            return redirect(action('ForgotPasswordController@forgot'))->with('message', 'The link you clicked is no longer valid. Please enter your email address again.');
        }

        Auth::login($User);

        return redirect(action('AccountController@password'));
    }

    public function logout()
    {
        $this->auth->logout();

        return redirect('/');
    }
}
