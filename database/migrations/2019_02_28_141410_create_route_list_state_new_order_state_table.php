<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRouteListStateNewOrderStateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('route_list_state_new_order_state', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('route_list_state_id')->unsigned();
            $table->integer('current_order_state_id')->unsigned();
            $table->integer('new_order_state_id')->unsigned();
            $table->timestamps();

            $table->unique(
                [
                    'route_list_state_id',
                    'current_order_state_id',
                    'new_order_state_id',
                ],
                'new_state'
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('route_list_state_new_order_state');
    }
}
