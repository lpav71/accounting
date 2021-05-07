<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTaskTypeUserDisabledBalancerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_type_user_disabled_balancer', function (Blueprint $table) {
            $table->integer('task_type_id')->unsigned();
            $table->integer('user_id')->unsigned();

            $table->primary(['task_type_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('task_type_user_disabled_balancer');
    }
}
