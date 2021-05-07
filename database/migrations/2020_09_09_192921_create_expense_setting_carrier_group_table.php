<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExpenseSettingCarrierGroupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('carrier_group_expense_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('expense_settings_id');
            $table->unsignedInteger('carrier_group_id');
            $table->timestamps();

            $table->foreign('expense_settings_id')->references('id')->on('expense_settings');
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
        Schema::table('carrier_group_expense_settings', function (Blueprint $table) {
            $table->dropForeign('carrier_group_expense_settings_expense_settings_id_foreign');
            $table->dropForeign('carrier_group_expense_settings_carrier_group_id_foreign');
        });
        Schema::dropIfExists('carrier_group_expense_settings');
    }
}
