<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeValuesTypesToTextInModelChanges extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('model_changes', function (Blueprint $table) {
            $table->text('old_value')->change();
            $table->text('new_value')->change();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('model_changes', function (Blueprint $table) {
            $table->string('old_value')->change();
            $table->string('new_value')->change();
        });
    }
}
