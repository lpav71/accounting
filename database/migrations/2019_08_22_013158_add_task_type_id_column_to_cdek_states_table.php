<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTaskTypeIdColumnToCdekStatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            'cdek_states',
            function (Blueprint $table) {
                $table->integer('task_type_id')->unsigned();
            }
        );

        DB::query()->from('cdek_states')->update(
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
            'cdek_states',
            function (Blueprint $table) {
                //
            }
        );
    }
}
