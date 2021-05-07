<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCarrierRuleOrderPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('carrier_rule_order_permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('rule_order_permission_id');
            $table->unsignedInteger('carrier_id');
            $table->timestamps();

            $table->foreign('rule_order_permission_id')->references('id')->on('rule_order_permissions')->onDelete('cascade');
            $table->foreign('carrier_id')->references('id')->on('carriers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_state_rating_rules', function (Blueprint $table) {
            $table->dropForeign('carrier_rule_order_permissions_rule_order_permission_id_foreign');
            $table->dropForeign('carrier_rule_order_permissions_carrier_id_foreign');
        });
        Schema::dropIfExists('carrier_rule_order_permissions');
    }
}
