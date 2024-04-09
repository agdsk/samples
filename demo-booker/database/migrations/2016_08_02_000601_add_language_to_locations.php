<?php

use Illuminate\Database\Migrations\Migration;

class AddLanguageToLocations extends Migration
{
    public function up()
    {
        Schema::table('locations', function ($table) {
            $table->string('language')->default('en-US')->after('type');
        });
    }

    public function down()
    {
        Schema::table('locations', function ($table) {
            $table->dropColumn('language');
        });
    }
}
