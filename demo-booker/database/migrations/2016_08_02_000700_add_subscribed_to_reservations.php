<?php

use Illuminate\Database\Migrations\Migration;

class AddSubscribedToReservations extends Migration
{
    public function up()
    {
        Schema::table('reservations', function ($table) {
            $table->tinyInteger('subscribed')->default(1)->after('email');
        });
    }

    public function down()
    {
        Schema::table('reservations', function ($table) {
            $table->dropColumn('subscribed');
        });
    }
}
