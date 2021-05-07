<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHttpTestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('http_tests', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('test_type');
            $table->integer('test_id')->unsigned();
            $table->boolean('is_active')->default(0);
            $table->boolean('is_message')->default(0);
            $table->string('url');
            $table->integer('period');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('http_tests');
    }
}
