<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndicatorColumnsToUtmGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            'utm_groups',
            function (Blueprint $table) {
                $table->integer('indicator_clicks_from')->nullable();
                $table->integer('indicator_clicks_to')->nullable();
                $table->float('indicator_price_per_click_from')->nullable();
                $table->float('indicator_price_per_click_to')->nullable();
                $table->float('indicator_price_per_order_from')->nullable();
                $table->float('indicator_price_per_order_to')->nullable();
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
            'utm_groups',
            function (Blueprint $table) {
                $table->dropColumn(
                    [
                        'indicator_clicks_from',
                        'indicator_clicks_to',
                        'indicator_price_per_click_from',
                        'indicator_price_per_click_to',
                        'indicator_price_per_order_from',
                        'indicator_price_per_order_to',
                    ]
                );
            }
        );
    }
}
