<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRuleOrderPermissionUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rule_order_permission_users', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('rule_order_permission_id');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('rule_order_permission_id')->references('id')->on('rule_order_permissions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rule_order_permission_users', function (Blueprint $table) {
            $table->dropForeign('rule_order_permission_users_user_id_foreign');
            $table->dropForeign('rule_order_permission_users_rule_order_permission_id_foreign');
        });
        Schema::dropIfExists('rule_order_permission_users');
    }
}
