<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Models\Location;
use App\Models\Reservation;
use App\Models\Script;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class ReservationsController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->isAdmin()) {
            $Locations = Location::all();
        } else {
            $Locations = Auth::user()->Locations;
        }

        $data = [
            'Locations' => $Locations,
        ];

        return view('reservations/index', $data);
    }

    public function location(Request $request, $id, $date = null)
    {
        $Location = Location::find($id);

        if (!$Location) {
            abort(404);
        }

        if (!$date) {
            $DateTime = new \DateTime('now', new \DateTimeZone($Location->timezone));

            $date = $DateTime->format('Y-m-d');
        }

        list($year, $month, $day) = explode('-', $date);

        $date_string = date('l, F j', mktime(0, 0, 0, $month, $day, $year));
        $date_prev   = date('Y-m-d', mktime(0, 0, 0, $month, $day - 1, $year));
        $date_next   = date('Y-m-d', mktime(0, 0, 0, $month, $day + 1, $year));
        $schedule    = $Location->getScheduleForDay($year, $month, $day);

        $Reservations = Reservation::where('date', $date)->where('status', '>=', 0)->where('location_id', $id)->orderBy('first_name')->get();

        $ReservationsByTime = [];

        foreach ($Reservations as $Reservation) {
            $ReservationsByTime[$Reservation->time][] = $Reservation;
        }

        $stats = [
            'total'   => count($Reservations),
            'website' => count($Reservations->filter(function ($Reservation) {

                return $Reservation->source == 'website';
            })),
            'walkup'  => count($Reservations->filter(function ($Reservation) {
                return $Reservation->source == 'walkup';
            })),
        ];

        $Today = new \DateTime('now', new \DateTimeZone($Location->timezone));

        $data = [
            'Location'        => $Location,
            'date_string'     => $date_string,
            'date'            => $date,
            'date_prev'       => $date_prev,
            'date_next'       => $date_next,
            'current_minutes' => $Today->format('G'),
            'schedule'        => array_keys($schedule['hours']),
            'Reservations'    => $ReservationsByTime,
            'Scripts'         => Script::all(),
            'stats'           => $stats,
        ];

        return view('reservations/location', $data);
    }

    public function createReservation(Request $request, $id)
    {
        $Location = Location::find($id);

        list($year, $month, $day) = explode('-', $request->input('date'));

        $data = [
            'Location' => $Location,
            'date'     => $request->input('date'),
            'time'     => $request->input('time'),
            'start'    => $Location->getStartTimeForDay($year, $month, $day),
            'end'      => $Location->getEndTimeForDay($year, $month, $day),
            'strings'  => $Location->language_strings,
        ];

        return view('reservations/create', $data);
    }

    public function saveReservation(Request $request)
    {
        $this->validate($request, [
            'first_name'  => 'string|required|max:255',
            'last_name'   => 'string|required|max:255',
            'email'       => 'email|required',
            'date'        => 'required|date',
            'location_id' => 'required|numeric|min:1',
            'time'        => 'required|numeric|min:0|max:1440',
        ]);

        // Get the location
        $Location = Location::find($request->input('location_id'));

        $Reservation = new Reservation();

        $Reservation->first_name  = $request->input('first_name');
        $Reservation->last_name   = $request->input('last_name');
        $Reservation->email       = $request->input('email');
        $Reservation->date        = $request->input('date');
        $Reservation->location_id = $request->input('location_id');
        $Reservation->time        = $request->input('time');
        $Reservation->phone       = $request->input('phone');
        $Reservation->source      = 'walkup';

        if ($Location->country == 'USA') {
            $Reservation->subscribed = 1;
        } else {
            $Reservation->subscribed = $request->input('subscribed') ? 1 : 0;
        }

        $Reservation->save();

        return redirect(action('ReservationsController@location', $Location->id))->with('message', 'Reservation created!');
    }

    public function cancelReservation(Request $request)
    {
        $Reservation = Reservation::find($request->input('reservation_id'));

        if (!$Reservation) {
            App::abort(404);
        }

        $Reservation->cancel();
    }

    public function checkinReservation(Request $request)
    {
        $Reservation = Reservation::find($request->input('reservation_id'));

        if (!$Reservation) {
            App::abort(404);
        }

        $Reservation->checkin();
    }

    public function demoReservation(Request $request)
    {
        $Reservation = Reservation::find($request->input('reservation_id'));

        if (!$Reservation) {
            App::abort(404);
        }

        $Reservation->complete();
    }
}
