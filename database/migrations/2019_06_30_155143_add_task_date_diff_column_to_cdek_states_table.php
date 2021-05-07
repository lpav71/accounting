<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTaskDateDiffColumnToCdekStatesTable extends Migration
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
                $table->smallInteger('task_date_diff')->unsigned()->default(0)->after('task_description');
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
        Schema::table(
            'cdek_states',
            function (Blueprint $table) {
                $table->dropColumn('task_date_diff');
            }
        );
    }
}
