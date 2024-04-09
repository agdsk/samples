<?php namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;

class ReminderController extends Controller
{
    public function index(Request $request)
    {
        return view('reminder/index');
    }

    public function create(Request $request)
    {
        $validation_rules = [
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|max:255|unique:users,email',
        ];

        $this->validate($request, $validation_rules);

        $Mailchimp = new \Mailchimp(env('MAILCHIMP'));

        $mc_email      = ['email' => $request->input('email')];
        $mc_merge_vars = [
            'FNAME' => $request->input('first_name'),
            'LNAME' => $request->input('last_name'),
        ];

        $mc_double_optin    = false;
        $mc_update_existing = true;

        $Mailchimp->lists->subscribe('cc39bd394c', $mc_email, $mc_merge_vars, 'html', $mc_double_optin, $mc_update_existing);

        return redirect('/')->with('message', 'Subscriber added!');
    }
}
