<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsDeletableRoutePointsColumnToRouteListStatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            'route_list_states',
            function (Blueprint $table) {
                $table
                    ->boolean('is_deletable_route_points')
                    ->default(1)
                    ->after('is_editable_route_list');
            }
        );

        DB::table('route_list_states')
            ->where('is_editable_route_list', 0)
            ->update(['is_deletable_route_points' => 0]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(
            'route_list_states',
            function (Blueprint $table) {
                $table->dropColumn(['is_deletable_route_points']);
            }
        );
    }
}
