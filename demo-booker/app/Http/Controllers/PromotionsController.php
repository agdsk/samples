<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Models\Location;
use App\Models\Promotion;
use Illuminate\Http\Request;

class PromotionsController extends Controller
{
    public function index(Request $request)
    {
        $data = [
            'Promotions' => Promotion::where('status', '>=', 0)->get(),
        ];

        return view('promotions/index', $data);
    }

    public function create(Request $request)
    {
        $data = [
            'Promotion' => new Promotion(),
            'Locations' => Location::all(),
        ];

        return view('promotions/create', $data);
    }

    public function store(Request $request)
    {
        $Promotion = new Promotion();

        return $this->save($request, $Promotion);
    }

    private function save($request, $Promotion)
    {
        $request->flash();

        $this->validate($request, [
            'code'  => 'required|string|max:255',
            'start' => 'required|date',
            'end'   => 'required|date',
            'size'  => 'required|numeric|min:1',
            'limit' => 'required|numeric|min:1',
        ]);

        $Promotion->code        = $request->input('code');
        $Promotion->location_id = $request->input('location_id');
        $Promotion->start       = $request->input('start');
        $Promotion->end         = $request->input('end');
        $Promotion->size        = $request->input('size');
        $Promotion->limit       = $request->input('limit');

        $Promotion->save();

        return redirect(route('promotions.index'));
    }

    public function edit(Request $request, $id)
    {
        $data = [
            'Promotion' => Promotion::find($id),
            'Locations' => Location::all(),
        ];

        return view('promotions/edit', $data);
    }

    public function update(Request $request, $id)
    {
        $Promotion = Promotion::find($id);

        return $this->save($request, $Promotion)->with('message', 'Your changes have been saved');;
    }

    public function destroy(Request $request, $id)
    {
        $Promotion = Promotion::find($id);

        $Promotion->status = -1;

        $Promotion->save();

        return redirect(route('promotions.index'))->with('message', 'Your changes have been saved');;
    }
}
