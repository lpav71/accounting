<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFlagsToReturnAndExchange extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_exchange_states', function (Blueprint $table){
            $table->boolean('is_successful')->default(0);
            $table->boolean('is_failure')->default(0);
        });

        Schema::table('product_return_states', function (Blueprint $table){
            $table->boolean('is_failure')->default(0);
            $table->boolean('is_successful')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_exchange_states', function (Blueprint $table) {
            $table->dropColumn([
                'is_successful',
                'is_failure'
            ]);
        });

        Schema::table('product_return_states', function (Blueprint $table) {
            $table->dropColumn([
                'is_successful',
                'is_failure'
            ]);
        });
    }
}
