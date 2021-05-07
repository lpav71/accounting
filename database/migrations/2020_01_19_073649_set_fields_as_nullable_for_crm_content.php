<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SetFieldsAsNullableForCrmContent extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->string('download_address')->nullable()->change();
        });
        Schema::table('product_pictures', function (Blueprint $table) {
            $table->integer('ordering')->nullable()->change();
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
            $table->string('download_address')->nullable(false)->change();
        });
        Schema::table('product_pictures', function (Blueprint $table) {
            $table->integer('ordering')->nullable(false)->change();
        });
    }
}
