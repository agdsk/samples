<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Models\Brand;
use App\Models\Location;
use App\Models\Override;
use DB;
use Illuminate\Http\Request;

class ToolsController extends Controller
{
    public function findInvalidReservations(Request $request)
    {
        $ReservationRepairer = app()->make('ReservationReparier');

        $data = [
            'InvalidReservations' => $ReservationRepairer->getInvalidReservations(),
        ];

        return view('tools/invalid_reservations', $data);
    }

    public function findInvalidReservationsCancel(Request $request)
    {
        $ReservationRepairer = app()->make('ReservationReparier');
        $Reservations        = $ReservationRepairer->getInvalidReservations();

        foreach ($Reservations as $Reservation) {
            $Reservation->cancel();
        }

        return redirect(action('ToolsController@findInvalidReservations'))->with('message', 'Invalid reservations cancelled');
    }

    public function findDuplicateLocations(Request $request)
    {
        $query = 'CONCAT(address, address2, city, region)';

        $addresses = DB::table('locations')->select(DB::raw('count(*), ' . $query . ' AS concatenated'))
            ->groupBy(DB::raw($query))
            ->having(DB::RAW('COUNT(*)'), '>', '1')
            ->pluck('concatenated');

        $Locations = Location::whereIn(DB::RAW($query), $addresses)->orderBy(DB::Raw($query))->get();

        $data = [
            'Locations' => $Locations,
        ];

        return view('tools/duplicate_locations', $data);
    }

    public function massOverrideForm(Request $request)
    {
        $Brands = Brand::all();

        $data = [
            'Brands' => $Brands,
        ];

        return view('tools/mass_override_creator', $data);
    }

    public function massOverrideCreate(Request $request)
    {
        $Brand = Brand::findOrFail($request->input('brand_id'));

        foreach ($Brand->Locations as $Location) {
            $Override              = new Override();
            $Override->location_id = $Location->id;
            $Override->date        = $request->input('date');
            $Override->start       = $request->input('start');
            $Override->end         = $request->input('end');
            $Override->stations    = $request->input('stations');

            $Override->save();
        }

        return redirect(action('ToolsController@findInvalidReservations'))->with('message', 'Overrides created, review potential reservation conflicts');
    }
}
