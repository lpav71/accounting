<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCarrierGroupUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('carrier_group_user', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('carrier_group_id');
            $table->unsignedInteger('user_id');
            $table->timestamps();

            $table->foreign('carrier_group_id')->references('id')->on('carrier_groups');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('carrier_group_user', function (Blueprint $table) {
            $table->dropForeign('carrier_group_user_carrier_group_id_foreign');
            $table->dropForeign('carrier_group_user_user_id_foreign');
        });
        Schema::dropIfExists('carrier_group_user');
    }
}
