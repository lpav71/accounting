<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMaximumAndMinimumCostsToUtmGroups extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('utm_groups', function (Blueprint $table){
            $table->float('minimum_costs')->nullable();
            $table->float('maximum_costs')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('utm_groups', function (Blueprint $table){
            $table->dropColumn(
                [
                    'minimum_costs',
                    'maximum_costs'
                ]
            );
        });
    }
}
