<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColorColumnToRouteListStatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('route_list_states', function (Blueprint $table) {
            $table->string('color')->default('#FFFFFF')->after('is_create_currency_operations');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('route_list_states', function (Blueprint $table) {
            $table->dropColumn(['color']);
        });
    }
}
