<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsCreateCurrencyOperationsToRouteListStatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('route_list_states', function (Blueprint $table) {
            $table->boolean('is_create_currency_operations')->default(0)->after('is_editable_route_list');
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
            $table->dropColumn(['is_create_currency_operations']);
        });
    }
}
