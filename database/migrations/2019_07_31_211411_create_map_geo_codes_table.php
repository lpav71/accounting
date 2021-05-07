<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMapGeoCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'map_geo_codes',
            function (Blueprint $table) {
                $table->increments('id');
                $table->string('hash');
                $table->float('geoX')->nullable();
                $table->float('geoY')->nullable();
                $table->timestamps();

                $table->unique('hash');
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
        Schema::dropIfExists('map_geo_codes');
    }
}
