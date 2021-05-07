<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRoutePointRoutePointStateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('route_point_route_point_state', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('route_point_id')->unsigned();
            $table->integer('route_point_state_id')->unsigned();
            $table->timestamps();

            $table->index(['route_point_id', 'route_point_state_id'], 'states_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('route_point_route_point_state');
    }
}
