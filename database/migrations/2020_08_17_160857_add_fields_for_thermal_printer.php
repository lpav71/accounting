<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsForThermalPrinter extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->string('check_title')->default('');
            $table->string('check_user')->default('');
            $table->string('inn')->default('');
            $table->string('ogrn')->default('');
            $table->string('kpp')->default('');
            $table->string('check_place')->default('');
            $table->string('check_qr_code')->default('');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn([
                'check_title',
                'check_user',
                'inn',
                'ogrn',
                'kpp',
                'check_place',
                'check_qr_code'
            ]);
        });
    }
}
