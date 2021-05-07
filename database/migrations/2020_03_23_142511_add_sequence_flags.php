<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSequenceFlags extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_exchange_states', function (Blueprint $table){
            $table->boolean('next_auto_closing_status')->default(0);
        });

        Schema::table('order_states', function (Blueprint $table){
            $table->boolean('next_auto_closing_status')->default(0);
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
                'next_auto_closing_status'
            ]);
        });

        Schema::table('order_states', function (Blueprint $table) {
            $table->dropColumn([
                'next_auto_closing_status'
            ]);
        });
    }
}
