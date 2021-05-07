<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsStoreAutotransferSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('store_autotransfer_settings', function (Blueprint $table) {
            $table->unsignedInteger('max_day')->after('settings')->default(0);
            $table->unsignedInteger('min_day')->after('settings')->default(0);
            $table->unsignedInteger('latest_sales_days')->after('settings')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('store_autotransfer_settings', function (Blueprint $table) {
            $table->dropColumn(['max_day','min_day','latest_sales_days']);
        });
    }
}
