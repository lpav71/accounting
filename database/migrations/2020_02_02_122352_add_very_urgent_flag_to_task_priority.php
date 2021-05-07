<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddVeryUrgentFlagToTaskPriority extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('task_priorities', function (Blueprint $table){
            $table->boolean('is_very_urgent')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('task_priorities', function (Blueprint $table) {
            $table->dropColumn([
                'is_very_urgent'
            ]);
        });
    }
}
