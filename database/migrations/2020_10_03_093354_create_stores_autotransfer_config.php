<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStoresAutotransferConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('store_autotransfer_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('main_store_id');
            $table->unsignedInteger('reserve_store_id');
            $table->string('name');
            $table->unsignedInteger('max_amount');
            $table->text('settings')->nullable();
            $table->timestamps();

            $table->foreign('main_store_id')->references('id')->on('stores');
            $table->foreign('reserve_store_id')->references('id')->on('stores');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('store_autotransfer_settings');
    }
}
