<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRoutePointStatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('route_point_states', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('new_order_state_id')->unsigned()->nullable();
            $table->integer('new_product_return_state_id')->unsigned()->nullable();
            $table->integer('new_product_exchange_state_id')->unsigned()->nullable();
            $table->boolean('is_detach_point_object')->default(0);
            $table->string('color')->default('#FFFFFF');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('route_point_states');
    }
}
