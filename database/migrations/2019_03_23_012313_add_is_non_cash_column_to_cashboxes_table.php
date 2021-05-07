<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsNonCashColumnToCashboxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            'cashboxes',
            function (Blueprint $table) {
                $table->boolean('is_non_cash')->default(0);
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
            'cashboxes',
            function (Blueprint $table) {
                $table->dropColumn(['is_non_cash']);
            }
        );
    }
}
