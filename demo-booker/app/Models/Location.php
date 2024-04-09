<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use function App\asset;
use function App\env;
use function App\resource_path;

class Location extends Model
{
    const PREFERRED_DATE_FORMAT = 'Y-m-d';

    public static $available_statuses = [
        -1 => 'Deleted',
        0  => 'Inactive',
        1  => 'Active',
    ];

    public static $available_types = [
        'retail'   => 'Retail',
        'tour'     => 'Tour',
        'anywhere' => 'Anywhere',
        'event'    => 'Event',
    ];

    public static $available_languages = [
        'en-CA' => 'English - Canada',
        'en-US' => 'English - United States',
        'en-GB' => 'English - United Kingdom',
        'fr-CA' => 'French - Canada',
        'fr-FR' => 'French - France',
        'de-DE' => 'German - Germany',
    ];

    public static $schedule_as_full_day_names = [
        7 => 'Sun',
        1 => 'Mon',
        2 => 'Tue',
        3 => 'Wed',
        4 => 'Thu',
        5 => 'Fri',
        6 => 'Sat',
    ];

    protected $appends = [
        'features',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'lat'            => 'float',
        'lng'            => 'float',
        'feature_gearvr' => 'bool',
        'feature_rift'   => 'bool',
        'feature_touch'  => 'bool',
        'gmtOffset'      => 'integer',
        'day_1_start'    => 'integer',
        'day_1_end'      => 'integer',
        'day_1_break'    => 'integer',
        'day_2_start'    => 'integer',
        'day_2_end'      => 'integer',
        'day_2_break'    => 'integer',
        'day_3_start'    => 'integer',
        'day_3_end'      => 'integer',
        'day_3_break'    => 'integer',
        'day_4_start'    => 'integer',
        'day_4_end'      => 'integer',
        'day_4_break'    => 'integer',
        'day_5_start'    => 'integer',
        'day_5_end'      => 'integer',
        'day_5_break'    => 'integer',
        'day_6_start'    => 'integer',
        'day_6_end'      => 'integer',
        'day_6_break'    => 'integer',
        'day_7_start'    => 'integer',
        'day_7_end'      => 'integer',
        'day_7_break'    => 'integer',
    ];

    // -----------------------------------------------------------------------------------------------------------------
    // Static
    // -----------------------------------------------------------------------------------------------------------------

    public static function toTime($input)
    {
        return date('g:ia', mktime(0, $input));
    }

    public static function toDate($input)
    {
        return date('m/d/y', strtotime($input));
    }

    public static function getRegions()
    {
        return DB::table('locations')->select(DB::raw('DISTINCT(CONCAT(country, ":", region)) AS region'))->orderBy('country')->orderBy('region')->get();
    }

    public static function getBrands()
    {
        return DB::table('brands')->select('id', DB::raw('name'))->orderBy('name')->get();
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Utility
    // -----------------------------------------------------------------------------------------------------------------

    public function hideMoreFields()
    {
        for ($i = 1; $i <= 7; $i++) {
            $this->hidden[] = 'day_' . $i . '_start';
            $this->hidden[] = 'day_' . $i . '_end';
            $this->hidden[] = 'day_' . $i . '_break';
        }

        $this->hidden[] = 'status';
        $this->hidden[] = 'stations';
        $this->hidden[] = 'feature_gearvr';
        $this->hidden[] = 'feature_rift';
        $this->hidden[] = 'feature_touch';
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Overrides
    // -----------------------------------------------------------------------------------------------------------------

    public function Save(array $options = [])
    {
        if ($this->lat == '' || $this->lng == '') {
            $this->setCoordinatesFromGoogle();
        }

        if (!array_key_exists('timezone', $this->attributes) || $this->attributes['timezone'] == '') {
            $this->setTimezoneFromGoogle();
        }

        return parent::save($options);
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Fancy Setters
    // -----------------------------------------------------------------------------------------------------------------

    public function setCoordinatesFromGoogle()
    {
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . str_replace(' ', '+', $this->full_address) . '&key=' . env('GOOGLE_API_KEY');

        $cache_key = 'location_coordinates_' . md5($url);

        if (Cache::has($cache_key)) {
            $response = Cache::get($cache_key);
        } else {
            $response = file_get_contents($url);
            $response = json_decode($response);

            try {
                Cache::put($cache_key, $response, 60);
            } catch (\Exception $e) {
            }
        }

        $this->lat = $response->results[0]->geometry->location->lat;
        $this->lng = $response->results[0]->geometry->location->lng;
    }

    public function setTimezoneFromGoogle()
    {
        $timestamp = mktime(date('h'), 0, 0, date('m'), date('j'));

        $url = 'https://maps.googleapis.com/maps/api/timezone/json?location=' . $this->lat . ',' . $this->lng . '&timestamp=' . $timestamp . '&key=' . env('GOOGLE_API_KEY');

        $cache_key = 'location_timezone_' . md5($url);

        if (Cache::has($cache_key)) {
            $response = Cache::get($cache_key);
        } else {
            $response = file_get_contents($url);
            $response = json_decode($response);

            try {
                Cache::put($cache_key, $response, 60);
            } catch (\Exception $e) {
            }
        }

        $this->timezone = $response->timeZoneId;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Date Getters
    // -----------------------------------------------------------------------------------------------------------------

    public function getStartDateTime()
    {
        $Schedule = Schedule::with('Location')->where('location_id', $this->id)->orderBy('start', 'ASC')->limit(1)->first();

        if (!$Schedule) {
            return null;
        }

        return new \DateTime($Schedule->start, new \DateTimeZone($this->timezone));
    }

    public function getEndDateTime()
    {
        $Schedule = Schedule::where('location_id', $this->id)->orderBy('end', 'DESC')->limit(1)->first();

        if (!$Schedule) {
            return null;
        }

        return new \DateTime($Schedule->end, new \DateTimeZone($this->timezone));
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Fancy Getters
    // -----------------------------------------------------------------------------------------------------------------

    function getNextAvailableDate($max_to_check, $Promotion = null)
    {
        // Create a DateTime object for today
        $TargetDate = new \DateTime('now', new \DateTimeZone($this->timezone));

        // Assume there is no first available demo
        $this->first_available_demo = null;

        for ($i = 0; $i <= $max_to_check; $i++) {
            // Add one day to the TargetDate
            $schedule = $this->getScheduleForDay($TargetDate->format('Y'), $TargetDate->format('m'), $TargetDate->format('d'), $Promotion);

            if ($schedule['available'] > 0) {
                return $TargetDate->format('Y-m-d');
                break;
            }

            $TargetDate->add(new \DateInterval('P1D'));
        }

        return null;
    }

    public function getStartTimeForDay($passedYear, $passedMonth, $passedDay)
    {
        $schedule = $this->getScheduleForDay($passedYear, $passedMonth, $passedDay);

        if (empty($schedule['hours'])) {
            return null;
        }

        reset($schedule['hours']);

        return key($schedule['hours']);
    }

    public function getEndTimeForDay($passedYear, $passedMonth, $passedDay)
    {
        $schedule = $this->getScheduleForDay($passedYear, $passedMonth, $passedDay);

        if (empty($schedule['hours'])) {
            return null;
        }

        end($schedule['hours']);

        return key($schedule['hours']);
    }

    public function getScheduleForDay($year, $month, $day)
    {
        // Get a DateTime object for Today. Results can be different if they are for the current day
        $Today      = new \DateTime('now', new \DateTimeZone($this->timezone));
        $TargetDate = new \DateTime($year . '-' . $month . '-' . $day, new \DateTimeZone($this->timezone));

        // Initialize the schedule
        $schedule['day_of_week_short'] = $TargetDate->format('D');
        $schedule['day_of_week_full']  = $TargetDate->format('l');
        $schedule['promotion']         = null; // The promotion used is initialized to null
        $schedule['available']         = 0; // The number of available slots initializes to 0 and may be incremented later
        $schedule['timeslots']         = []; // Timeslots contains a list of each timeslot and the number of demos available ine ach
        $schedule['hours']             = []; // Hours contains a list of each timeslot with a boolean indicating if it contains any available demos

        // Initialize the start and end times
        $this->start = null;
        $this->end   = null;

        // Get Start and End times
        $StartDateTime = $this->getStartDateTime();
        $EndDateTime   = $this->getEndDateTime();

        // Return the empty schedule if there is no start or end time defined
        if (!$StartDateTime instanceof \DateTime || !$EndDateTime instanceof \DateTime) {
            return $schedule;
        }

        // Set the start and end times
        $this->start = $this->getStartDateTime()->format(Location::PREFERRED_DATE_FORMAT);
        $this->end   = $this->getEndDateTime()->format(Location::PREFERRED_DATE_FORMAT);

        // Return the empty schedule if the TargetDate is outside the bounds of the start and end time
        if ($StartDateTime > $TargetDate || $EndDateTime < $TargetDate) {
            return $schedule;
        }

        $Schedule = Schedule::where('start', '<=', $TargetDate->format(Location::PREFERRED_DATE_FORMAT))
            ->where('end', '>=', $TargetDate->format(Location::PREFERRED_DATE_FORMAT))
            ->where('location_id', $this->id)
            ->orderBy('id', 'DESC')
            ->first();

        if (!$Schedule) {
            return $schedule;
        }

        // Determine which Location object properties will be used
        $start       = $Schedule->getAttribute('day_' . $TargetDate->format('N') . '_start');
        $break_start = $Schedule->getAttribute('day_' . $TargetDate->format('N') . '_break_start');
        $break_end   = $Schedule->getAttribute('day_' . $TargetDate->format('N') . '_break_end');
        $end         = $Schedule->getAttribute('day_' . $TargetDate->format('N') . '_end');
        $stations    = $Schedule->getAttribute('stations');

        // See if there is an override defined for this day
        $Override = Override::where('location_id', $this->id)->where('date', $TargetDate->format(Location::PREFERRED_DATE_FORMAT))->first();

        // If there is an overide for this day, then the start time, end time, number of stations and size of each
        // station will be overridden
        if ($Override) {
            $start    = $Override->start;
            $end      = $Override->end;
            $stations = $Override->stations;
        }

        // Return the empty schedule if there are no start or end times for the TargetDate
        if (!$start || !$end) {
            return $schedule;
        }

        // Initialize the timeslots to the standard availability
        for ($i = $start; $i <= $end; $i += 30) {
            $schedule['timeslots'][$i] = $stations;
        }

        // Now obtain a count of all the demos that have already been booked
        $existing_reservations = DB::table('reservations')
            ->select(DB::raw('time, count(*) as count'))
            ->where('location_id', $this->id)
            ->where('status', '>=', 0)
            ->where('date', $TargetDate->format('Y-m-d'))
            ->groupBy('time')
            ->get();

        // Subtract the number of demos that have already been booked from the availability for each timeslot
        foreach ($existing_reservations as $existing_reservation) {
            $schedule['timeslots'][$existing_reservation->time] = $stations - $existing_reservation->count;
        }

        // Determine which timeslots are available to book
        for ($i = $start; $i <= $end; $i += 30) {
            if ($i >= $break_start && $i < $break_end) {
                $schedule['timeslots'][$i] = 0;
                $schedule['hours'][$i]     = false;
//            } elseif ($break_start == ($i - 30) && in_array($this->id, [598, 599])) {
//                // Special behavior hacked in for two locations only. Their break is 1 hour long
//                $schedule['timeslots'][$i] = 0;
//                $schedule['hours'][$i]     = false;
            } elseif ($Today->format('Y-m-d') == $TargetDate->format('Y-m-d')) {
                // If the day in question is today, then there must be room in the slot and the timeslot must not
                // have passed for the day

                // The current_minutes is the total number of minutes that have elapsed so far today
                $current_minutes = ($Today->format('G') * 60) + $Today->format('i');

                // If the time has passed, timeslots[$i] available is 0. Otherwise it is the original number of timeslots
                $schedule['timeslots'][$i] = ($i < $current_minutes) ? 0 : $schedule['timeslots'][$i];

                // If the time is passed, hours[$i] is false. Otherwise it is true if there are timeslots available
                $schedule['hours'][$i] = ($schedule['timeslots'][$i] > 0) && ($i > $current_minutes);
            } else {
                // Otherwise, just having demos available in the timeslot is sufficient
                $schedule['hours'][$i] = $schedule['timeslots'][$i] > 0;
            }
        }

        // Count the number of available timeslots
        $schedule['available'] = count(array_filter($schedule['hours']));

        // Nice work!
        return $schedule;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Accessors
    // -----------------------------------------------------------------------------------------------------------------

    public function getLanguageStringsAttribute()
    {
        $language = strtolower($this->language);

        $file = resource_path('locales/' . $language . '.json');

        if (!file_exists($file)) {
            $file = resource_path('locales/en-us.json');
        }

        $file_contents = file_get_contents($file);

        $file_contents = str_replace("\xEF\xBB\xBF", '', $file_contents);

        $json = json_decode($file_contents, true);

        return $json;
    }

    // Best Buy #251|Microsoft Store
    public function getBrandedNameAttribute()
    {
        // Hard coded special case for Best Buy
        // @TODO Abstract this somehow
        if ($this->brand_id == 2) {
            return 'Best Buy #' . $this->vendor_id;
        }

        return $this->name;
    }

    // America/New_York
    public function getTimezoneAttribute()
    {
        if (!array_key_exists('timezone', $this->attributes) || $this->attributes['timezone'] == '') {
            return 'America/New_York';
        }

        return $this->attributes['timezone'];
    }

    // -34.397,150.644
    public function getLatLngAttribute()
    {
        return $this->lat . ',' . $this->lng;
    }

    // 1 Facebook Way,Menlo Park,CA
    public function getFullAddressAttribute()
    {
        return trim($this->address . ' ' . $this->address2) . ',' . $this->city . ',' . $this->region . ',' . $this->country;
    }

    // 1 Facebook Way, Menlo Park, CA
    public function getFullFriendlyAddressAttribute()
    {
        return trim($this->address . ' ' . $this->address2) . ', ' . $this->city . ', ' . $this->region;
    }

    // Menlo Park, CA|
    public function getFullCityAttribute()
    {
        return $this->city ? $this->city . ', ' . $this->region : '';
    }

    // images/dot-green.png|images/dot-grey.png
    public function getStatusImageAttribute()
    {
        return $this->status > 0 ? asset('images/dot-green.png') : asset('images/dot-grey.png');
    }

    public function getFeaturesAttribute()
    {
        return [
            'rift'   => ['Rift', (bool)$this->attributes['feature_rift']],
            'touch'  => ['Touch', (bool)$this->attributes['feature_touch']],
            'gearvr' => ['Gear VR', (bool)$this->attributes['feature_gearvr']],
        ];
    }

    public function getFeaturesListAttribute()
    {
        $features = [];

        if ($this->attributes['feature_rift']) {
            $features[] = 'Rift';
        }

        if ($this->attributes['feature_touch']) {
            $features[] = 'Touch';
        }

        if ($this->attributes['feature_gearvr']) {
            $features[] = 'Gear VR';
        }

        return implode(', ', $features);
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------------------------------------------------

    public function Brand()
    {
        return $this->belongsTo('App\Models\Brand');
    }

    public function Users()
    {
        return $this->belongsToMany('App\Models\User');
    }

    public function Overrides()
    {
        return $this->hasMany('App\Models\Override');
    }

    public function Schedules()
    {
        return $this->hasMany('App\Models\Schedule');
    }
}
