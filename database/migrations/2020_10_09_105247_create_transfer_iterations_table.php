<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransferIterationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transfer_iterations', function (Blueprint $table) {
            $table->increments('id');
            $table->text('settings');
            $table->boolean('is_completed')->default(0);
            $table->integer('transfered_count')->default(0);
            $table->unsignedInteger('store_id_from');
            $table->unsignedInteger('store_id_to');
            $table->timestamps();

            $table->foreign('store_id_from')->references('id')->on('stores');
            $table->foreign('store_id_to')->references('id')->on('stores');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transfer_iterations');
    }
}
