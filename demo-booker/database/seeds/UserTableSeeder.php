<?php

use App\Models\User;
use Illuminate\Database\Seeder;

class UserTableSeeder extends Seeder
{
    public static $users = [
        "xxx|xxx|xxx@xx.com",
    ];

    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('users')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // -------------------------------------------------------------------------------------------------------------
        // xxx employees
        // -------------------------------------------------------------------------------------------------------------

        foreach (self::$users as $record) {
            list($first_name, $last_name, $email_address) = explode('|', $record);

            User::create([
                'first_name' => $first_name,
                'last_name'  => $last_name,
                'email'      => $email_address,
                'role'       => 30
            ]);
        }

        // -------------------------------------------------------------------------------------------------------------
        // CSV users
        // -------------------------------------------------------------------------------------------------------------

        // Get all the records from the CSV file
        $locations_and_users_data = file(__DIR__ . '/reps.csv');

        // Don't delete this. The first character is not a normal space, but some weird whitespace that shows up
        // in the vendor supplied csv
        $locations_and_users_data = array_map(function ($record) {
            return str_replace(' ', ' ', $record);
        }, $locations_and_users_data);

        // Shift off the first row of data to use as keys
        $location_and_users_keys = str_getcsv(array_shift($locations_and_users_data));

        // Sanitize the keys so they make useful array indexes
        $location_and_users_keys = array_map(function ($key) {
            $key = strtolower($key);
            $key = str_replace(' ', '_', $key);

            return $key;
        }, $location_and_users_keys);

        $sets_of_keys = [
            ['manager', 'manager_email_address', 20],
            ['users', 'user_email_address', 10],
            ['backup_rep', 'backup_rep_email_address', 10],
        ];

        foreach ($sets_of_keys as $set_of_keys) {
            $key_name  = $set_of_keys['0'];
            $key_email = $set_of_keys['1'];
            $role      = $set_of_keys['2'];

            foreach ($locations_and_users_data as $record) {
                $record = array_combine($location_and_users_keys, str_getcsv($record));

                if ($record[$key_email] == '') {
                    continue;
                }

                $User = User::where('email', $record[$key_email])->first();

                if (!$User) {
                    list($first_name, $last_name) = explode(' ', $record[$key_name], 2);

                    User::create([
                        'first_name' => $first_name,
                        'last_name'  => $last_name,
                        'email'      => $record[$key_email],
                        'role'       => $role,
                        'status'     => 1,
                    ]);
                }
            }
        }
    }
}