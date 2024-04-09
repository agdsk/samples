<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLocationsTable extends Migration
{
    public function up()
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('brand_id')->unsigned();
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('restrict')->onUpdate('cascade');
            $table->string('timezone');
            $table->string('name');
            $table->string('vendor_id');
            $table->string('address');
            $table->string('address2');
            $table->string('city');
            $table->string('region');
            $table->string('postalCode');
            $table->string('country');
            $table->date('start')->nullable()->default(null);
            $table->date('end')->nullable()->default(null);
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->tinyInteger('status')->default(0);
            $table->tinyInteger('stations')->unsigned()->default(0);

            for ($i = 1; $i <= 7; $i++) {
                $table->smallInteger('day_' . $i . '_start')->unsigned()->nullable()->default(null);
                $table->smallInteger('day_' . $i . '_end')->unsigned()->nullable()->default(null);
                $table->smallInteger('day_' . $i . '_break')->unsigned()->nullable()->default(null);
            }

            $table->tinyInteger('feature_gearvr')->unsigned()->default(0);
            $table->tinyInteger('feature_rift')->unsigned()->default(0);
            $table->tinyInteger('feature_touch')->unsigned()->default(0);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop('locations');
    }
}