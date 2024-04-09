<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSchedulesTable extends Migration
{
    public function up()
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('location_id')->unsigned();
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('restrict')->onUpdate('cascade');
            $table->date('start')->nullable()->default(null);
            $table->date('end')->nullable()->default(null);
            $table->tinyInteger('stations')->unsigned()->default(0);

            for ($i = 1; $i <= 7; $i++) {
                $table->smallInteger('day_' . $i . '_start')->unsigned()->nullable()->default(null);
                $table->smallInteger('day_' . $i . '_end')->unsigned()->nullable()->default(null);
                $table->smallInteger('day_' . $i . '_break_start')->unsigned()->nullable()->default(null);
                $table->smallInteger('day_' . $i . '_break_end')->unsigned()->nullable()->default(null);
            }

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop('schedules');
    }
}