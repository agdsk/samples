<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Models\Location;
use App\Models\Override;
use App\Models\Reservation;
use Illuminate\Http\Request;

class LocationsOverridesController extends Controller
{
    public function create(Request $request, $id)
    {
        $Location = Location::find($id);

        $data = [
            'Location' => $Location,
        ];

        return view('locations/overrides/create', $data);
    }

    public function store(Request $request, $id)
    {
        $request->flash();

        $this->validate($request, [
            'date'  => 'required|date',
            'start' => 'required_with:end',
            'end'   => 'required_with:start|greater_than_field:start',
        ], [
            'required_with'      => 'Both a start time and an end time are required',
            'greater_than_field' => 'Start time must be before end time',
        ]);

        $Reservations = Reservation::where('location_id', $request->input('location_id'));
        $Reservations = $Reservations->where('date', $request->input('date'));
        $Reservations = $Reservations->where('status', '>=', 0);

        $Reservations = $Reservations->where(function ($query) use ($request) {
            if ($request->input('start')) {
                $query = $query->where('time', '<', $request->input('start'));
            }

            if ($request->input('end')) {
                $query = $query->orWhere('time', '>', $request->input('end'));
            }
        });

        $Reservations = $Reservations->orderBy('time')->get();

        if ($Reservations->count()) {
            $Location = Location::find($id);

            $data = [
                'Location'     => $Location,
                'Reservations' => $Reservations,
                'stations'     => $request->input('stations'),
            ];

            if (!$request->input('behavior') || !in_array($request->input('behavior'), ['keep', 'cancel'])) {
                return view('locations/overrides/create', $data);
            }

            if ($request->input('behavior') == 'cancel') {
                foreach ($Reservations as $Reservation) {
                    $Reservation->cancel();
                }
            }
        }

        $Override              = new Override();
        $Override->location_id = $request->input('location_id');
        $Override->date        = $request->input('date');
        $Override->start       = $request->input('start');
        $Override->end         = $request->input('end');
        $Override->stations    = $request->input('stations');

        $Override->save();

        return redirect(route('locations.show', $Override->location_id))->with('message', 'Your changes have been saved');;
    }

    public function destroy(Request $request, $location_id, $override_id)
    {
        $Override = Override::find($override_id);

        $Override->delete();

        return redirect(route('locations.show', $location_id))->with('message', 'Your changes have been saved');;
    }
}
