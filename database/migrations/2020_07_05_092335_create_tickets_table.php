<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->unsignedInteger('order_id')->nullable();
            $table->unsignedInteger('creator_user_id');
            $table->unsignedInteger('performer_user_id')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders');
            $table->foreign('creator_user_id')->references('id')->on('users');
            $table->foreign('performer_user_id')->references('id')->on('users');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign('tickets_order_id_foreign');
            $table->dropForeign('tickets_creator_user_id_foreign');
            $table->dropForeign('tickets_performer_user_id_foreign');
        });

        Schema::dropIfExists('tickets');
    }
}
