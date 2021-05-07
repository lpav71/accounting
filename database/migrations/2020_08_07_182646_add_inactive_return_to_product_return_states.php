<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInactiveReturnToProductReturnStates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_return_states', function (Blueprint $table) {
            $table->boolean('inactive_return')->default(0);
            $table->boolean('shipment_available')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_return_states', function (Blueprint $table) {
            $table->dropColumn('inactive_return');
            $table->dropColumn('shipment_available');
        });
    }
}
