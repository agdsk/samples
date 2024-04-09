<?php

use Illuminate\Database\Migrations\Migration;

class AddReservationsToLocations extends Migration
{
    public function up()
    {
        Schema::table('locations', function ($table) {
            $table->tinyInteger('reservations')->default(1)->after('type');
        });
    }

    public function down()
    {
        Schema::table('locations', function ($table) {
            $table->dropColumn('reservations');
        });
    }
}
