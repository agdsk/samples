<?php namespace App\Http\Controllers;

use App\Http\Requests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function password(Request $request)
    {
        return view('account/account');
    }

    public function passwordPost(Request $request)
    {
        $this->validate($request, [
            'password' => 'required|confirmed',
        ]);

        $errors = [];

        if (strlen($request->input('password')) < 8) {
            $errors[] = "be at least 8 characters long";
        }

        if (!preg_match("#[0-9]+#", $request->input('password'))) {
            $errors[] = "contain at least one number";
        }

        if (!preg_match("#[a-zA-Z]+#", $request->input('password'))) {
            $errors[] = "contain at least one letter";
        }

        if ($errors) {
            if (count($errors) > 1) {
                $errors[count($errors) - 1] = 'and ' . $errors[count($errors) - 1];
            }

            $error_message = "Your password must " . implode(", ", $errors);

            return redirect(action('AccountController@password'))->withErrors(['password' => $error_message]);
        }

        Auth::user()->password = $request->input('password');
        Auth::user()->save();

        return redirect('/')->with('message', 'Your password has been changed');
    }
}
