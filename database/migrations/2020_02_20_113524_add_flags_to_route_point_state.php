<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFlagsToRoutePointState extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('route_point_states', function (Blueprint $table){
            $table->boolean('is_successful')->default(0);
        });
        Schema::table('route_point_states', function (Blueprint $table){
            $table->boolean('is_failure')->default(0);
        });
        Schema::table('route_point_states', function (Blueprint $table){
            $table->boolean('is_new')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('route_point_states', function (Blueprint $table) {
            $table->dropColumn([
                'is_successful',
                'is_failure',
                'is_new'
            ]);
        });
    }
}
