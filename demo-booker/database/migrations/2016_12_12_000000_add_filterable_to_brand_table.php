<?php

use Illuminate\Database\Migrations\Migration;

class AddFilterableToBrandTable extends Migration
{
    public function up()
    {
        Schema::table('brands', function ($table) {
            $table->tinyInteger('filterable')->default(1)->after('status');
        });
    }

    public function down()
    {
        Schema::table('brands', function ($table) {
            $table->dropColumn('filterable');
        });
    }
}
