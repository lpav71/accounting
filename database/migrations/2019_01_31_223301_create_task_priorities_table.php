<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTaskPrioritiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_priorities', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->smallInteger('rate')->default(0);
            $table->timestamps();
        });

        \App\TaskPriority::create([
            'name' => __('Normal priority'),
            'rate' => 50,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('task_priorities');
    }
}
