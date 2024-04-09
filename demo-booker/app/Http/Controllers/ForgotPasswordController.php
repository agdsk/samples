<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Models\PasswordResetToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordController extends Controller
{
    public function forgot()
    {
        return view('auth.forgot-password');
    }

    public function forgotPost(Request $request)
    {
        $request->flash();

        $User = User::where('email', $request->input('email'))->first();

        if (!$User) {
            return redirect(action('ForgotPasswordController@forgot'))->withErrors(['email' => 'This email address was not found']);
        }

        $PasswordResetToken = PasswordResetToken::where('email', $User->email)->first();

        if (!$PasswordResetToken) {
            $PasswordResetToken = new PasswordResetToken();
        }

        $PasswordResetToken->email = $User->email;
        $PasswordResetToken->token = bin2hex(openssl_random_pseudo_bytes(32));

        $PasswordResetToken->save();

        $mandrill         = new \Mandrill(env('MANDRILL'));
        $template_content = [];
        $message          = [
            'to'                => [
                [
                    'email' => $User->email,
                    'name'  => $User->first_name . ' ' . $User->last_name,
                    'type'  => 'to',
                ],
            ],
            'global_merge_vars' => [
                [
                    'name'    => 'WHO',
                    'content' => $User->first_name,
                ],
                [
                    'name'    => 'LINK',
                    'content' => action('AuthController@token', $PasswordResetToken->token)
                ],
            ],
        ];

        $mandrill->messages->sendTemplate('forgot-password', $template_content, $message);

        return redirect(action('ForgotPasswordController@forgot'))->with('message', 'Check your email! A password reset message has been sent to you.');
    }
}
