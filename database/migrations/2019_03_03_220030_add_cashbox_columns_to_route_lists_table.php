<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCashboxColumnsToRouteListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('route_lists', function (Blueprint $table) {
            $table->float('accepted_funds')->default(0)->after('date_list');
            $table->float('costs')->default(0)->after('accepted_funds');
            $table->integer('currency_id')->unsigned()->nullable()->after('costs');
            $table->integer('cashbox_id')->unsigned()->nullable()->after('currency_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('route_lists', function (Blueprint $table) {
            $table->dropColumn([
                'accepted_funds',
                'costs',
                'currency_id',
                'cashbox_id'
            ]);
        });
    }
}
