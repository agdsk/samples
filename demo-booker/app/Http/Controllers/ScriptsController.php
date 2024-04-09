<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Models\Script;
use Illuminate\Http\Request;

class ScriptsController extends Controller
{
    public function index(Request $request)
    {
        $data = [
            'Scripts' => Script::all(),
        ];

        return view('scripts/index', $data);
    }

    public function create(Request $request)
    {
        $data = [
            'Script' => new Script(),
        ];

        return view('scripts/create', $data);
    }

    public function show(Request $request, $id)
    {
        $Script = Script::find($id);

        $data = [
            'Script' => $Script,
        ];

        return view('scripts/show', $data);
    }

    public function store(Request $request)
    {
        $Script = new Script();

        return $this->save($request, $Script);
    }

    public function edit(Request $request, $id)
    {
        $data = [
            'Script' => Script::find($id),
        ];

        return view('scripts/edit', $data);
    }

    public function update(Request $request, $id)
    {
        $Script = Script::find($id);

        return $this->save($request, $Script);
    }

    private function save($request, $Script)
    {
        $request->flash();

        $this->validate($request, [
            'name' => 'required|string|max:255',
        ]);

        $Script->name = $request->input('name');
        $Script->body = $request->input('body');

        $Script->save();

        return redirect(route('scripts.index'))->with('message', 'Your changes have been saved');
    }

    public function destroy(Request $request, $id)
    {
        $Script = Script::find($id);

        $Script->delete();

        return redirect(route('scripts.index'))->with('message', 'Your changes have been saved');
    }
}
