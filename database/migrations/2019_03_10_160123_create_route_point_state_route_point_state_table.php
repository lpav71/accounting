<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRoutePointStateRoutePointStateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('route_point_state_route_point_state', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('route_point_state_id')->unsigned();
            $table->integer('previous_state_id')->unsigned();
            $table->timestamps();

            $table->unique(['route_point_state_id', 'previous_state_id'], 'previous_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('route_point_state_route_point_state');
    }
}
