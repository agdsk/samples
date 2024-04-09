<?php namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Services_Twilio;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::check()) {
            return redirect(action('AuthController@login'));
        }

        if (Auth::user()->isAdmin()) {
            return view('home');
        }

        if (Auth::user()->isManager()) {
            return view('home');
        }

        if (Auth::user()->isAmbassador()) {
            return redirect(action('ReservationsController@index'));
        }
    }
}
