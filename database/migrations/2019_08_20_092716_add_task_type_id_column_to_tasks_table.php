<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTaskTypeIdColumnToTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            'tasks',
            function (Blueprint $table) {
                $table->integer('task_type_id')->unsigned();
            }
        );

        DB::query()->from('tasks')->update(
            ['task_type_id' => \App\TaskType::query()->orderByDesc('name')->first()->id]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(
            'tasks',
            function (Blueprint $table) {
                $table->dropColumn('task_type_id');
            }
        );
    }
}
