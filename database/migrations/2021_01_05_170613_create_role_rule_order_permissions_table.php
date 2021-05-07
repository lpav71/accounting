<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRoleRuleOrderPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('role_rule_order_permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('rule_order_permission_id');
            $table->unsignedInteger('role_id');
            $table->timestamps();

            $table->foreign('rule_order_permission_id')->references('id')->on('rule_order_permissions')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('role_rule_order_permissions', function (Blueprint $table) {
            $table->dropForeign('role_rule_order_permissions_rule_order_permission_id_foreign');
            $table->dropForeign('role_rule_order_permissions_role_id_foreign');
        });
        Schema::dropIfExists('role_rule_order_permissions');
    }
}
