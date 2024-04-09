<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Library\ReservationReparier;
use App\Models\Brand;
use App\Models\Location;
use App\Models\Schedule;
use App\Models\User;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LocationsController extends Controller
{
    public function repair(Request $request, $id)
    {
        $Location = Location::find($id);

        if (Gate::denies('show-location', $Location)) {
            abort(403);
        }

        $ReservationRepairer = app()->make('ReservationReparier');

        $Reservations = $ReservationRepairer->getInvalidReservations($Location);

        foreach ($Reservations as $Reservation) {
            $Reservation->cancel();
        }

        return redirect(route('locations.show', $Location->id))->with('message', 'The invalid reservations have been cancelled');
    }

    public function index(Request $request)
    {
        $region_selected = $request->query('region');
        $brand_selected  = $request->query('brand_id');

        if (strstr($region_selected, ':')) {
            list($target_country, $target_region) = explode(':', $region_selected);
        }

        if ($brand_selected) {
            $target_brand_id = $brand_selected;
        }

        if (Auth::user()->isAdmin()) {
            $Locations = Location::where('status', '>=', 0)->with('Users');
            $Locations = $Locations->with('Brand');

            if ($request->input('brand_id')) {
                $Locations = $Locations->where('brand_id', $request->input('brand_id'));
            }

            if (isset($target_country)) {
                $Locations = $Locations->where('country', $target_country);
            }

            if (isset($target_region)) {
                $Locations = $Locations->where('region', $target_region);
            }

            if (isset($target_brand_id)) {
                $Locations = $Locations->where('brand_id', $target_brand_id);
            }

            $Locations = $Locations->get();
        } else {
            $Locations = Auth::user()->Locations;
        }

        $data = [
            'regions'         => Location::getRegions(),
            'brands'          => Location::getBrands(),
            'region_selected' => $region_selected,
            'brand_selected'  => $brand_selected,
            'Locations'       => $Locations,
        ];

        return view('locations/index', $data);
    }

    public function show(Request $request, $id)
    {
        $Location = Location::find($id);

        if (Gate::denies('show-location', $Location)) {
            abort(403);
        }

        $ReservationRepairer = app()->make('ReservationReparier');

        $data = [
            'Users'               => User::all(),
            'Location'            => $Location,
            'InvalidReservations' => $ReservationRepairer->getInvalidReservations($Location),
        ];

        return view('locations/show', $data);
    }

    public function create(Request $request)
    {
        if (Gate::denies('create-location')) {
            abort(403);
        }

        $Location          = new Location();
        $Location->visible = 1;

        $data = [
            'Users'           => User::all(),
            'Location'        => $Location,
            'Brands'          => Brand::all(),
            'schedules_array' => [],
        ];

        return view('locations/create', $data);
    }

    public function store(Request $request)
    {
        if (Gate::denies('create-location')) {
            abort(403);
        }

        $Location = new Location();

        return $this->save($request, $Location);
    }

    public function edit(Request $request, $id)
    {
        $Location = Location::find($id);

        if (Gate::denies('update-location', $Location)) {
            abort(403);
        }

        $Schedules = Schedule::where('location_id', $id)->get();

        $schedules_array = $Schedules->toArray();

        $schedules_array = array_map(function ($schedule) {
            $schedule['operation'] = 'update';

            return $schedule;
        }, $schedules_array);

        $data = [
            'Users'           => User::all(),
            'Location'        => $Location,
            'Brands'          => Location::getBrands(),
            'schedules_array' => $schedules_array,
        ];

        return view('locations/edit', $data);
    }

    public function update(Request $request, $id)
    {
        $Location = Location::find($id);

        if (Gate::denies('update-location', $Location)) {
            abort(403);
        }

        return $this->save($request, $Location);
    }

    private function save($request, $Location)
    {
        $request->flash();

        $this->validate($request, [
            'name'       => 'required|string|max:255',
            'vendor_id'  => 'required|string',
            'address'    => 'required|string|max:255',
            'address2'   => 'string|max:255',
            'city'       => 'required|string|max:255',
            'region'     => 'string|max:255',
            'country'    => 'required|string|max:255',
            'postalCode' => 'required|string|max:255',
        ]);

        if (Gate::allows('administer-location')) {
            $Location->brand_id     = $request->input('brand_id');
            $Location->type         = $request->input('type');
            $Location->visible      = $request->input('visible');
            $Location->reservations = $request->input('reservations');
            $Location->status       = $request->input('status');
        }

        $Location->name           = $request->input('name');
        $Location->vendor_id      = $request->input('vendor_id');
        $Location->address        = $request->input('address');
        $Location->address2       = $request->input('address2');
        $Location->city           = $request->input('city');
        $Location->region         = $request->input('region');
        $Location->country        = $request->input('country');
        $Location->postalCode     = $request->input('postalCode');
        $Location->language       = $request->input('language');
        $Location->feature_gearvr = $request->input('feature_gearvr');
        $Location->feature_rift   = $request->input('feature_rift');
        $Location->feature_touch  = $request->input('feature_touch');

        $Location->save();

        $user_ids = array_filter($request->input('users'));

        $Location->Users()->sync($user_ids);

        if ($request->input('schedules')) {
            foreach ($request->input('schedules') as $schedule) {
                switch ($schedule['operation']) {
                    case 'update' :
                        unset($schedule['operation']);
                        $Schedule = Schedule::find($schedule['id']);
                        $Schedule->unguard();
                        $Schedule->fill($schedule);
                        $Schedule->Save();
                        break;

                    case 'create' :
                        unset($schedule['operation']);
                        unset($schedule['id']);
                        $Schedule = new Schedule();
                        $Schedule->unguard();
                        $Schedule->location_id = $Location->id;
                        $Schedule->fill($schedule);
                        $Schedule->Save();
                        break;

                    case 'delete' :
                        $Schedule = Schedule::find($schedule['id']);
                        $Schedule->delete();
                        break;
                }
            }
        }

        return redirect(route('locations.show', $Location->id))->with('message', 'Your changes have been saved');
    }

    public function destroy(Request $request, $id)
    {
        $Location = Location::find($id);

        if (Gate::denies('delete-location', $Location)) {
            abort(403);
        }

        $Location->status = -1;

        $Location->save();

        return redirect(route('locations.index'))->with('message', 'Your changes have been saved');
    }
}
