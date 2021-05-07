<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTelephonyAccountUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('telephony_account_group_user', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('telephony_account_group_id');
            $table->unsignedInteger('user_id');
            $table->timestamps();

            $table->foreign('telephony_account_group_id','tagu_telephony_account_group_id_foreign')->references('id')->on('telephony_account_groups');
            $table->foreign('user_id')->references('id')->on('users');
        });

        Schema::create('telephony_account_telephony_account_group',function (Blueprint $table){
            $table->increments('id');
            $table->unsignedInteger('telephony_account_id');
            $table->unsignedInteger('telephony_account_group_id');
            $table->timestamps();

            $table->foreign('telephony_account_id','tatag_telephony_account_id_foreign')->references('id')->on('telephony_accounts');
            $table->foreign('telephony_account_group_id','tatag_telephony_account_group_id_foreign')->references('id')->on('telephony_account_groups');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('telephony_account_group_user', function (Blueprint $table) {
            $table->dropForeign('tagu_telephony_account_group_id_foreign');
            $table->dropForeign('telephony_account_group_user_user_id_foreign');
        });

        Schema::dropIfExists('telephony_account_group_user');

        Schema::table('telephony_account_telephony_account_group',function (Blueprint $table){
            $table->dropForeign('tatag_telephony_account_id_foreign');
            $table->dropForeign('tatag_telephony_account_group_id_foreign');
        });

        Schema::dropIfExists('telephony_account_telephony_account_group');
    }
}
