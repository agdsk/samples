<?php

use Illuminate\Database\Migrations\Migration;

class AddTypeToLocations extends Migration
{
    public function up()
    {
        Schema::table('locations', function ($table) {
            $table->string('type')->default('retail')->after('brand_id');
        });
    }

    public function down()
    {
        Schema::table('locations', function ($table) {
            $table->dropColumn('type');
        });
    }
}
