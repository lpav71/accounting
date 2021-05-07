<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCarrierGroupRuleOrderPerms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('carrier_group_rule_order_perms', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('rule_order_permission_id');
            $table->unsignedInteger('carrier_group_id');
            $table->timestamps();

            $table->foreign('rule_order_permission_id')->references('id')->on('rule_order_permissions')->onDelete('cascade');
            $table->foreign('carrier_group_id')->references('id')->on('carrier_groups')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_carrier_group_rule_order_perms', function (Blueprint $table) {
            $table->dropForeign('carrier_group_rule_order_perms_rule_order_permission_id_foreign');
            $table->dropForeign('carrier_group_rule_order_perms_carrier_group_id_foreign');
        });
        Schema::dropIfExists('carrier_group_rule_order_perms');
    }
}
