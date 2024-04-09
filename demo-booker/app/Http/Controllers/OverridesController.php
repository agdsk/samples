<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Models\Override;
use Illuminate\Http\Request;

class OverridesController extends Controller
{
    public function __construct() {

    }

    public function index(Request $request)
    {
        $data = [
            'Overrides' => Override::with('Location')->get(),
        ];

        return view('overrides/index', $data);
    }

    public function create(Request $request)
    {
        $data = [
            'Override' => new Override(),
        ];

        return view('overrides/create', $data);
    }

    public function store(Request $request)
    {
        $Override = new Override();

        return $this->save($request, $Override);
    }

    public function edit(Request $request, $id)
    {
        $data = [
            'Override' => Override::find($id),
        ];

        return view('overrides/edit', $data);
    }

    public function update(Request $request, $id)
    {
        $Override = Override::find($id);

        return $this->save($request, $Override);
    }

    private function save($request, $Override)
    {
        $request->flash();

        $this->validate($request, [
//            'name' => 'required|string|max:255',
//            'slug' => 'required|string|alpha-dash',
        ]);

//        $Override->name                  = $request->input('name');
//        $Override->slug                  = $request->input('slug');
//        $Override->img_bg_url            = $request->input('img_bg_url');
//        $Override->img_logo_large_url    = $request->input('img_logo_large_url');
//        $Override->long_text_description = $request->input('long_text_description');
//        $Override->show_map              = $request->input('show_map');

        $Override->save();

        return redirect(route('overrides.index'))->with('message', 'Your changes have been saved');
    }

    public function destroy(Request $request, $id)
    {
        $Override = Override::find($id);

        $Override->delete();

        return redirect(route('overrides.index'))->with('message', 'Your changes have been saved');
    }
}
