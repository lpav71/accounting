<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderStateRuleOrderPerms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_state_rule_order_perms', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('rule_order_permission_id');
            $table->unsignedInteger('order_state_id');
            $table->timestamps();

            $table->foreign('rule_order_permission_id')->references('id')->on('rule_order_permissions')->onDelete('cascade');
            $table->foreign('order_state_id')->references('id')->on('order_states')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_state_rule_order_perms', function (Blueprint $table) {
            $table->dropForeign('order_state_rule_order_perms_rule_order_permission_id_foreign');
            $table->dropForeign('order_state_rule_order_perms_order_state_id_foreign');
        });
        Schema::dropIfExists('order_state_rule_order_perms');
    }
}
