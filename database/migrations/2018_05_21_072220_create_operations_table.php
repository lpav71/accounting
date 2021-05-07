<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOperationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('operations', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('type', ['C', 'D']);
            $table->integer('quantity')->unsigned();
            $table->text('comment');
            $table->integer('operable_id')->nullable();
            $table->string('operable_type')->nullable();
            $table->integer('storage_id')->nullable();
            $table->string('storage_type')->nullable();
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
        Schema::dropIfExists('operations');
    }
}
