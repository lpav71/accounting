<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCarrierGroupIdToCarrier extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('carriers', function (Blueprint $table) {
            $table->unsignedInteger('carrier_group_id')->nullable(true);

            $table->foreign('carrier_group_id')->references('id')->on('carrier_groups');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('carriers', function (Blueprint $table) {
            $table->dropForeign('carriers_carrier_group_id_foreign');
            $table->dropColumn([
                'carrier_group_id'
            ]);
        });
    }
}
