<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOverridesTable extends Migration
{
    public function up()
    {
        Schema::create('overrides', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('location_id')->unsigned();
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade')->onUpdate('cascade');
            $table->date('date');
            $table->smallInteger('start')->unsigned()->nullable()->default(null);
            $table->smallInteger('break')->unsigned()->nullable()->default(null);
            $table->smallInteger('end')->unsigned()->nullable()->default(null);
            $table->tinyInteger('stations')->unsigned()->default(0);
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop('overrides');
    }
}
