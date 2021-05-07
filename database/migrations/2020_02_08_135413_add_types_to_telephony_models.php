<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTypesToTelephonyModels extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->string('telephony_name');
        });
        Schema::table('call_events', function (Blueprint $table) {
            $table->string('telephony_name');
        });
        Schema::table('phone_events', function (Blueprint $table) {
            $table->string('telephony_name');
        });
        //before this migration all telephony information were from beeline

        \DB::table('calls')->update(['telephony_name'=>'beeline']);
        \DB::table('call_events')->update(['telephony_name'=>'beeline']);
        \DB::table('phone_events')->update(['telephony_name'=>'beeline']);

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn(['telephony_name']);
        });
        Schema::table('call_events', function (Blueprint $table) {
            $table->dropColumn(['telephony_name']);
        });
        Schema::table('phone_events', function (Blueprint $table) {
            $table->dropColumn(['telephony_name']);
        });
    }
}
