<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameValueInPrestaProductPrestaProductAttribute extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('presta_product_presta_product_attribute', function (Blueprint $table) {
            $table->renameColumn('value','attr_value');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('presta_product_presta_product_attribute', function (Blueprint $table) {
            $table->renameColumn('attr_value','value');
        });
    }
}
