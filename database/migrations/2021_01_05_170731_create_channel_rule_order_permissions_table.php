<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChannelRuleOrderPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('channel_rule_order_permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('rule_order_permission_id');
            $table->unsignedInteger('channel_id');
            $table->timestamps();

            $table->foreign('rule_order_permission_id')->references('id')->on('rule_order_permissions')->onDelete('cascade');
            $table->foreign('channel_id')->references('id')->on('channels')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('channel_rule_order_permissions', function (Blueprint $table) {
            $table->dropForeign('channel_rule_order_permissions_rule_order_permission_id_foreign');
            $table->dropForeign('channel_rule_order_permissions_channel_id_foreign');
        });
        Schema::dropIfExists('channel_rule_order_permissions');
    }
}
