<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Models\Location;
use App\Models\User;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->isAdmin()) {
            $Users = User::where('status', '>=', 0)->orderBy('last_name')->orderBy('first_name')->get();
        } elseif (Auth::user()->isManager()) {
            $Users = User::where('status', '>=', 0)->orderBy('last_name')->orderBy('first_name')->where('role', 10)->get();
        }

        $data = [
            'Users' => $Users,
        ];

        return view('users/index', $data);
    }

    public function create(Request $request)
    {
        $data = [
            'User'      => new User(),
            'Locations' => Location::all(),
        ];

        return view('users/create', $data);
    }

    public function store(Request $request)
    {
        $User = new User();

        return $this->save($request, $User);
    }

    public function edit(Request $request, $id)
    {
        $User = User::find($id);

        if (Gate::denies('touch-user', $User)) {
            abort(403);
        }

        $data = [
            'User'      => $User,
            'Locations' => Location::orderBy('name', 'vendor_id')->get(),
        ];

        return view('users/edit', $data);
    }

    public function update(Request $request, $id)
    {
        $User = User::find($id);

        return $this->save($request, $User);
    }

    private function save($request, $User)
    {
        $request->flash();

        $validation_rules = [
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|max:255|unique:users,email',
        ];

        if ($User->id) {
            $validation_rules['email'] = 'required|email|max:255|unique:users,email,' . $User->id;
        }

        $this->validate($request, $validation_rules);

        $User->first_name = $request->input('first_name');
        $User->last_name  = $request->input('last_name');
        $User->email      = $request->input('email');
        $User->status     = $request->input('status');

        if (Gate::allows('change-user-role')) {
            $User->role = $request->input('role');
        }

        $User->save();

        $location_ids = array_filter($request->input('locations'));

        $User->Locations()->sync($location_ids);

        return redirect(route('users.index'))->with('message', 'Your changes have been saved');
    }

    public function destroy(Request $request, $id)
    {
        if (Gate::denies('delete-user')) {
            abort(403);
        }

        $User = User::find($id);

        $User->delete();

        return redirect(route('users.index'))->with('message', 'Your changes have been saved');
    }
}
