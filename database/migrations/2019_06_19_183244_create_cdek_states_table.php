<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCdekStatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cdek_states', function (Blueprint $table) {
            $table->increments('id');
            $table->string('state_code');
            $table->boolean('need_task')->default(0);
            $table->string('task_name')->nullable();
            $table->string('task_description')->nullable();
            $table->integer('task_priority_id')->nullable();
            $table->boolean('is_daily')->default(0);
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
        Schema::dropIfExists('cdek_states');
    }
}
