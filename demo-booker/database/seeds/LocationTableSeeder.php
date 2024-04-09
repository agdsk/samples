<?php

use App\Models\Brand;
use App\Models\Location;
use App\Models\Override;
use App\Models\User;
use Illuminate\Database\Seeder;

class LocationTableSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('locations')->truncate();
        DB::table('location_user')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // -------------------------------------------------------------------------------------------------------------
        // CSV users
        // -------------------------------------------------------------------------------------------------------------

        // Get all the records from the CSV file
        $locations_and_users_data = file(__DIR__ . '/reps.csv');

        // Shift off the first row of data to use as keys
        $location_and_users_keys = str_getcsv(array_shift($locations_and_users_data));

        // Sanitize the keys so they make useful array indexes
        $location_and_users_keys = array_map(function ($key) {
            $key = strtolower($key);
            $key = str_replace(' ', '_', $key);

            return $key;
        }, $location_and_users_keys);

        $BrandBestBuy = Brand::where('name', 'Best Buy')->first();

        $schedule = [
            1 => ['start' => 810, 'end' => 1140, 'break' => 960, 'size' => 1],  // Monday
            2 => ['start' => null, 'end' => null, 'break' => null, 'size' => 0], // Tuesday
            3 => ['start' => null, 'end' => null, 'break' => null, 'size' => 0], // Wednesday
            4 => ['start' => 810, 'end' => 1140, 'break' => 960, 'size' => 1],  // Thursday
            5 => ['start' => 750, 'end' => 1140, 'break' => 960, 'size' => 1],  // Friday
            6 => ['start' => 720, 'end' => 1140, 'break' => 960, 'size' => 1],  // Saturday
            7 => ['start' => 690, 'end' => 1080, 'break' => 900, 'size' => 1],  // Sunday
        ];

        foreach ($locations_and_users_data as $record) {
            $record = array_combine($location_and_users_keys, str_getcsv($record));

            if ($record['location_name'] == '') {
                continue;
            }

            $Location                 = new Location();
            $Location->name           = $record['location_name'];
            $Location->vendor_id      = $record['store_number'];
            $Location->address        = $record['address'];
            $Location->city           = $record['city'];
            $Location->region         = $record['st'];
            $Location->postalCode     = $record['postal_code'];
            $Location->country        = $record['country'];
            $Location->start          = '2016-05-07';
            $Location->status         = 1;
            $Location->feature_rift   = 1;
            $Location->feature_touch  = 0;
            $Location->feature_gearvr = 0;

            foreach ($schedule as $day_number => $day_schedule) {
                $Location->setAttribute('day_' . $day_number . '_start', $day_schedule['start']);
                $Location->setAttribute('day_' . $day_number . '_end', $day_schedule['end']);
                $Location->setAttribute('day_' . $day_number . '_break', $day_schedule['break']);
            }

            $Location->Brand()->associate($BrandBestBuy);

            $Location->save();

            $User = User::where('email', $record['manager_email_address'])->first();
            $Location->Users()->attach($User);

            $User = User::where('email', $record['user_email_address'])->first();
            $Location->Users()->attach($User);

            $User = User::where('email', $record['backup_rep_email_address'])->first();
            $Location->Users()->attach($User);
        }
    }
}