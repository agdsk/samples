<?php

use Illuminate\Database\Migrations\Migration;

class AddVisibleToLocations extends Migration
{
    public function up()
    {
        Schema::table('locations', function ($table) {
            $table->tinyInteger('visible')->default(1)->after('type');
        });
        Schema::table('brands', function ($table) {
            $table->tinyInteger('show_map')->default(1)->after('slug');
            $table->text('long_text_description')->nullable()->after('status');
            $table->string('img_bg_url')->nullable()->after('long_text_description');
            $table->string('img_logo_large_url')->nullable()->after('img_bg_url');
        });
    }

    public function down()
    {
        Schema::table('locations', function ($table) {
            $table->dropColumn('visible');
        });
        Schema::table('brands', function ($table) {
            $table->dropColumn('show_map');
            $table->dropColumn('long_text_description');
            $table->dropColumn('img_bg_url');
            $table->dropColumn('img_logo_large_url');
        });
    }
}
