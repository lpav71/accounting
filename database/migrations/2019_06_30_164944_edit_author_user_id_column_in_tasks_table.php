<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditAuthorUserIdColumnInTasksTable extends Migration
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
                $table->integer('author_user_id')->unsigned()->nullable()->change();
            }
        );

        DB::table('cdek_state_order')->update(['task_made' => 1]);
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
                //
            }
        );
    }
}
