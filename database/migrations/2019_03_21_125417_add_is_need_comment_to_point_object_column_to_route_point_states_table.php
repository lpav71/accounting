<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsNeedCommentToPointObjectColumnToRoutePointStatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            'route_point_states',
            function (Blueprint $table) {
                $table
                    ->boolean('is_need_comment_to_point_object')
                    ->default(0)
                    ->after('is_attach_detached_point_object');
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
        Schema::table(
            'route_point_states',
            function (Blueprint $table) {
                $table->dropColumn(['is_need_comment_to_point_object']);
            }
        );
    }
}
