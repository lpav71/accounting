<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCashboxLimits extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cashboxes', function (Blueprint $table) {
            $table->integer('limit')->unsigned()->nullable(true);
            $table->integer('operation_limit')->unsigned()->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cashboxes', function (Blueprint $table) {
            $table->dropColumn([
                'limit',
                'operation_limit'
            ]);
        });
    }
}
