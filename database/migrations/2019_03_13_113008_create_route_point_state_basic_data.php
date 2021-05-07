<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRoutePointStateBasicData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $firstState = \App\RoutePointState::create(
            [
                'name' => __('New'),
            ]
        );

        \App\RoutePoint::all()->each(
            function (\App\RoutePoint $routePoint) use ($firstState) {
                if (is_null($routePoint->currentState())) {
                    $routePoint->states()->save($firstState);
                }
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
