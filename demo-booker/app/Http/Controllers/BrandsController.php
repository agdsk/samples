<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Models\Brand;
use Illuminate\Http\Request;

class BrandsController extends Controller
{
    public function index(Request $request)
    {
        $data = [
            'Brands' => Brand::all(),
        ];

        return view('brands/index', $data);
    }

    public function create(Request $request)
    {
        $data = [
            'Brand' => new Brand(),
        ];

        return view('brands/create', $data);
    }

    public function store(Request $request)
    {
        $Brand = new Brand();

        return $this->save($request, $Brand);
    }

    public function edit(Request $request, $id)
    {
        $data = [
            'Brand' => Brand::find($id),
        ];

        return view('brands/edit', $data);
    }

    public function update(Request $request, $id)
    {
        $Brand = Brand::find($id);

        return $this->save($request, $Brand);
    }

    private function save($request, $Brand)
    {
        $request->flash();

        $this->validate($request, [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|alpha-dash',
        ]);

        $Brand->name                  = $request->input('name');
        $Brand->slug                  = $request->input('slug');
        $Brand->img_bg_url            = $request->input('img_bg_url');
        $Brand->img_logo_large_url    = $request->input('img_logo_large_url');
        $Brand->long_text_description = $request->input('long_text_description');
        $Brand->show_map              = $request->input('show_map');

        $Brand->save();

        return redirect(route('brands.index'))->with('message', 'Your changes have been saved');
    }

    public function destroy(Request $request, $id)
    {
        $Brand = Brand::find($id);

        $Brand->delete();

        return redirect(route('brands.index'))->with('message', 'Your changes have been saved');
    }
}
