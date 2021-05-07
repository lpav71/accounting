<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropUnnecessaryColumnsInRouteLists extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('route_lists', function (Blueprint $table) {
            $table->dropColumn([
                'date_list',
                'currency_id',
                'cashbox_id',
                'store_id',
                'accepted_funds',
                'costs',
            ]);
        });

        Schema::drop('route_list_route_list_state');
        Schema::drop('route_list_state_route_list_state');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('route_lists', function (Blueprint $table){
            $table->dateTime('date_list')->nullable(false);
            $table->double('accepted_funds')->nullable(false)->default(0.00);
            $table->double('costs')->nullable(false)->default(0.00);
            $table->integer('currency_id')->unsigned()->nullable(true)->default(null);
            $table->integer('cashbox_id')->unsigned()->nullable(true)->default(null);
            $table->integer('store_id')->unsigned()->nullable(true)->default(null);
        });
    }
}
