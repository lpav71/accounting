<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUtmsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'utms',
            function (Blueprint $table) {
                $table->increments('id');
                $table->integer('utm_campaign_id')->unsigned()->nullable();
                $table->integer('utm_source_id')->unsigned()->nullable();
                $table->timestamps();

                $table->unique(['utm_campaign_id', 'utm_source_id']);
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('utms');
    }
}
