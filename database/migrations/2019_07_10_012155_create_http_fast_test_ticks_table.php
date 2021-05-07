<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHttpFastTestTicksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'http_fast_test_ticks',
            function (Blueprint $table) {
                $table->increments('id');
                $table->integer('http_fast_test_id')->unsigned();
                $table->integer('response_time')->unsigned()->default(0);
                $table->boolean('is_have_need_string_in_body')->default(0);
                $table->boolean('is_finished')->default(0);
                $table->boolean('is_error')->default(0);
                $table->text('message')->default('');
                $table->timestamps();
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
        Schema::dropIfExists('http_fast_test_ticks');
    }
}
