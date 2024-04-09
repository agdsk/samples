<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Jobs\SendReservationText;
use App\Models\Brand;
use App\Models\Location;
use App\Models\Promotion;
use App\Models\Reservation;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function listBrands(Request $request)
    {
        $Brands = Brand::orderBy('name')->get();

        $data = [
            'Brands' => $Brands,
        ];

        return response()->json($data, 200);
    }

    public function getLanguage(Request $request)
    {
        $normalize = function ($value) {
            if (!$value) {
                return null;
            }

            $value = str_replace('_', '-', $value);
            $value = strtolower($value);

            return $value;
        };

        $header = $request->header('accept-language');
        $cookie = array_key_exists('locale', $_COOKIE) ? $_COOKIE['locale'] : null;

        if (strpos($header, ':')) {
            list($locales, $quality) = explode(';', $header);
        } else {
            $locales = $header;
        }

        $languages = explode(',', $locales);

        array_push($languages, 'en-us');

        $languages = array_map($normalize, $languages);

        $data = [
            'cookie' => [
                'raw'        => $cookie,
                'normalized' => $normalize($cookie),
            ],
            'header' => [
                'raw'     => $header,
                'locales' => $languages,
            ],
        ];

        return response()->json($data, 200);
    }

    public function checkPromoCode(Request $request)
    {
        $Promotion = Promotion::where('code', $request->route('promotion'))->first();

        if (!$Promotion || $Promotion->status != 1) {
            return response()->json(['error' => 'Promotion not found'], 200);
        }

        return response()->json($Promotion);
    }

    public function createNotificationSignup(Request $request)
    {
        $Mailchimp = new \Mailchimp(env('MAILCHIMP'));

        $mc_email      = ['email' => $request->input('email')];
        $mc_merge_vars = [
            'SEARCHTERM' => $request->input('searchterm'),
            'LAT'        => $request->input('lat'),
            'LNG'        => $request->input('lng'),
        ];

        $mc_double_optin    = false;
        $mc_update_existing = true;

        $Mailchimp->lists->subscribe('45c392ca83', $mc_email, $mc_merge_vars, 'html', $mc_double_optin, $mc_update_existing);

        return response()->json(['success' => 'Notification created'], 200);
    }

    public function getAllLocations(Request $request)
    {
        // Only get Active locations
        $Locations = Location::where('status', 1)->where('visible', 1)->with('Brand');

        // If a 'brand' parameter was passed, only get locations of that brand
        if ($request->input('brand')) {
            $Brand = Brand::where('slug', $request->input('brand'))->first();

            if ($Brand) {
                $Locations = $Locations->where('brand_id', $Brand->id);
            }
        }

        // Get the locations
        $Locations = $Locations->get();

        // Hide many unnecessary fields from the resulting JSON output
        foreach ($Locations as $Location) {
            $Location->hideMoreFields();
        }

        // Returning an object like this will automatically convert it to JSON
        return $Locations;
    }

    // Returns brand info
    public function getBrand(Request $request, $slug)
    {
        $Brand = Brand::where('slug', $slug)->with('Locations')->first();

        $schedules_start = \DB::table('schedules')->select('location_id', 'start')->whereIn('location_id', $Brand->Locations->pluck('id'))->orderBy('start', 'ASC')->get();

        foreach ($schedules_start as $schedule) {
            $Location = $Brand->Locations->find($schedule->location_id);

            if ($Location) {
                $Location->start = $schedule->start;
            }
        }

        $schedules_end = \DB::table('schedules')->select('location_id', 'end')->whereIn('location_id', $Brand->Locations->pluck('id'))->orderBy('end', 'DESC')->get();

        foreach ($schedules_end as $schedule) {
            $Location = $Brand->Locations->find($schedule->location_id);

            if ($Location) {
                $Location->end = $schedule->end;
            }
        }

        return $Brand;
    }

    public function getLocation(Request $request, $id, $date = null, $range = 0)
    {
        // Ensure $range is valid
        if (!is_numeric($range) || $range > 3 || $range < 0) {
            abort(400);
        }

        // Get the location
        $Location = Location::with('Brand')->find($id);

        // If it could not be found, or is not active, return 404
        if (!$Location || $Location->status != 1) {
            if ($request->ajax()) {
                return response('Not found', 404);
            } else {
                abort(404);
            }
        }

        // If the 'promotion' parameter was passed, get the Promotion.
        // Otherwise initialize $Promotion to null
        if ($request->input('promotion')) {
            $Promotion = Promotion::where('code', $request->input('promotion'))->first();
        } else {
            $Promotion = null;
        }

        // Create DateTime objects for start and end dates
        $StartDate = $Location->getStartDateTime();
        $EndDate   = $Location->getEndDateTime();

        // Figure out how far ahead we're going to check
        if ($Location->type == 'tour') {
            $how_far_ahead = 999;
        } else {
            $how_far_ahead = 14;
        }

        // Get first available demo
        $Location->first_available_demo = $Location->getNextAvailableDate($how_far_ahead, $Promotion);

        // If a 'date' parameter was passed, also get the schedule for the date (or date range)
        if ($date && preg_match('/\d{4}-\d{2}-\d{2}/', $date)) {
            // Create a DateTime object for the target date
            $Today      = new \DateTime('now', new \DateTimeZone('UTC'));
            $TargetDate = new \DateTime($date, new \DateTimeZone('UTC'));

            // Now subtract a number of days equal to $range
            // This may be 0, which would mean the date won't change
            $TargetDate->sub(new \DateInterval('P' . ($range + 1) . 'D'));

            // Initialize the schedule
            $schedule = [];

            // Collect the daily schedules for $range days into the past and future
            // i.e. if your target date is a Thursday and your range is 2, you will get Tue, Wed, Thur, Fri and Sat
            for ($i = $range * -1; $i <= $range; $i++) {
                // Add one day to the TargetDate
                $TargetDate->add(new \DateInterval('P1D'));

                if ($StartDate > $TargetDate || $EndDate < $TargetDate || $TargetDate->diff($Today)->days > $how_far_ahead) {
                    $schedule[$TargetDate->format('Y-m-d')]['hours']     = [];
                    $schedule[$TargetDate->format('Y-m-d')]['timeslots'] = [];
                    $schedule[$TargetDate->format('Y-m-d')]['available'] = 0;

                    continue;
                }

                // Get the schedule for a single day. If a valid $Promotion was found, it may impact this day's availability
                // If $Promotion is null, it will be safely ignored
                $schedule[$TargetDate->format('Y-m-d')] = $Location->getScheduleForDay($TargetDate->format('Y'), $TargetDate->format('m'), $TargetDate->format('d'), $Promotion);
            }

            // Assign the schedule to the Location
            $Location->schedule = $schedule;
        }

        // Hide many unnecessary fields from the resulting JSON output
        $Location->hideMoreFields();

        if ($Location->country == 'USA') {
            $Location->subscribe = -1;
        } elseif ($Location->country == 'Canada') {
            $Location->subscribe = -1;
        } else {
            $Location->subscribe = 0;
        }

        // Returning an object like this will automatically convert it to JSON
        return $Location;
    }

    public function createReservation(Request $request)
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

        // If it could not be found, or if it is not active, return 404
        if (!$Location || $Location->status != 1) {
            if ($request->ajax()) {
                return response('Not found', 404);
            } else {
                abort(404);
            }
        }

        // If the 'promotion' parameter was passed, get the Promotion. Otherwise initialize $Promotion to null
        if ($request->input('promotion')) {
            $Promotion = Promotion::where('code', $request->input('promotion'))->first();
        } else {
            $Promotion = null;
        }

        list($year, $month, $day) = explode('-', $request->input('date'));

        $scheduleForDay = $Location->getScheduleForDay($year, $month, $day, $Promotion);

        if (!array_key_exists($request->input('time'), $scheduleForDay['hours'])) {
            return response()->json(['error' => 'No demos are available for the requested time. Please select another time'], 400);
        }

        if (!$scheduleForDay['hours'][$request->input('time')]) {
            return response()->json(['error' => 'No more demos are available for the requested time. Please select another time'], 400);
        }

        $Reservation = new Reservation();

        $Reservation->first_name  = $request->input('first_name');
        $Reservation->last_name   = $request->input('last_name');
        $Reservation->email       = $request->input('email');
        $Reservation->date        = $request->input('date');
        $Reservation->location_id = $request->input('location_id');
        $Reservation->time        = $request->input('time');
        $Reservation->phone       = $request->input('phone');
        $Reservation->source      = 'website';

        if ($Location->country == 'USA') {
            $Reservation->subscribed = 1;
        } elseif ($Location->country == 'Canada') {
            $Reservation->subscribed = 0;
        } else {
            $Reservation->subscribed = $request->input('subscribed') ? 1 : 0;
        }

        $Reservation->save();

        return $Reservation;
    }

    public function getReservation(Request $request)
    {
        $Reservation = Reservation::where('hash', $request->route('hash'))->first();

        if (!$Reservation) {
            abort(404);
        }

        if (!$Reservation->status == 0) {
            abort(404);
        }

        return $Reservation;
    }

    public function updateReservation(Request $request)
    {
        $Reservation = Reservation::where('hash', $request->route('hash'))->first();

        if (!$Reservation) {
            abort(404);
        }

        try {
            dispatch(new SendReservationText($Reservation));
        } catch (\Exception $e) {
            return response()->json(['error' => 'There was a problem sending a message to this phone number'], 500);
        }

        try {
            $Reservation->text = $request->input('text');
            $Reservation->save();
        } catch (\Exception $e) {
            return response()->json(['error' => 'There was a problem updating your reservation'], 500);
        }

        return response()->json(['success' => 'Reservation updated'], 200);
    }

    public function cancelReservation(Request $request)
    {
        $Reservation = Reservation::where('hash', $request->route('hash'))->first();

        if (!$Reservation) {
            abort(404);
        }

        $Reservation->customerCancel();

        return response()->json(['success' => 'Reservation cancelled'], 200);
    }

    public function getCalendarFile(Request $request)
    {
        $Reservation = Reservation::where('hash', $request->route('hash'))->first();

        if (!$Reservation) {
            abort(404);
        }

        $StartTime = $Reservation->DateTime();
        $EndTime   = clone $StartTime;
        $EndTime->add(new \DateInterval('PT30M'));

        $vCalendar = new \Eluceo\iCal\Component\Calendar('live.acme.com');

        $vEvent = new \Eluceo\iCal\Component\Event();
        $vEvent->setDtStart($StartTime);
        $vEvent->setDtEnd($EndTime);
        $vEvent->setLocation($Reservation->Location->full_friendly_address, $Reservation->Location->name, $Reservation->Location->lat_lng);
        $vEvent->setSummary('Acme Live Demo');
        $vEvent->setUseTimezone(true);

        $vCalendar->addComponent($vEvent);

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="My-Acme-Live-Demo.ics"');

        echo $vCalendar->render();

        exit;
    }
}
